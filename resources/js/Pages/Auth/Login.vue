<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticationCard from '@/Components/AuthenticationCard.vue'
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue'
import Checkbox from '@/Components/Checkbox.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import QRCode from 'qrcode'
import {ref, onMounted, computed} from 'vue'
import axios from 'axios'
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Modal from "@/Components/Modal.vue";
import Swal from 'sweetalert2';

const userdevice = ref('desktop') // безопасное значение по умолчанию

onMounted(async () => {
    try {
        const response = await axios.get('/api/deviceinfo')
        userdevice.value = response.data.device
    } catch (error) {
        console.error('Не удалось определить устройство:', error)
        // device остаётся 'desktop' по умолчанию — страница не виснет
    }
})

defineProps({
    canResetPassword: Boolean,
    status: String,
})

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}

const showQrModal = ref(false)
const uuid = ref(null)
const qrUrl = ref('')
const qrImage = ref('')
let echoChannel = null
const timeLeft = ref(300)
let timerInterval = null

const fetchQrSession = async () => {
    qrImage.value = ''

    if (echoChannel && uuid.value) {
        Echo.leave(`login.${uuid.value}`)
    }

    await axios.get('/sanctum/csrf-cookie')

    const res = await axios.post('/api/qr/session')

    uuid.value = res.data.uuid
    qrUrl.value = res.data.url
    qrImage.value = await QRCode.toDataURL(qrUrl.value)

    echoChannel = Echo.channel(`login.${uuid.value}`)
        .listen('.login.approved', async () => {
            await axios.post(`/qr/consume/${uuid.value}`)
            window.location.href = '/dashboard'
        })
        .listen('.login.rejected', async () => {
            const oldUuid = uuid.value

            Echo.leave(`login.${oldUuid}`)

            await closeQrModal()

            await Swal.fire({
                title: 'Вход был отклонён',
                icon: 'error',
                text: 'Пользователь отклонил вход.'
            })

            await axios.delete(`/api/qr/session/${oldUuid}`)
        })
}

const startTimer = () => {
    timeLeft.value = 300
    timerInterval = setInterval(() => {
        timeLeft.value--
        if (timeLeft.value <= 0) {
            clearInterval(timerInterval)
            fetchQrSession() // просто обновляем QR, модалка уже открыта
            startTimer() // запускаем таймер заново для нового кода
        }
    }, 1000)
}

const openQrModal = async () => {
    showQrModal.value = true
    await fetchQrSession()
    startTimer()
}

const closeQrModal = async () => {
    showQrModal.value = false
    clearInterval(timerInterval)

    if (uuid.value) {
        Echo.leave(`login.${uuid.value}`)
        try {
            await axios.delete(`/api/qr/session/${uuid.value}`)
        } catch (error) {
            console.error('Не удалось удалить QR-сессию:', error)
        }
        echoChannel = null
        uuid.value = null
    }
}

const formattedTime = computed(() => {
    const minutes = Math.floor(timeLeft.value / 60)
    const seconds = timeLeft.value % 60
    return `${minutes}:${seconds.toString().padStart(2, '0')}`
})
</script>

<template>
    <Head title="Log in" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox v-model:checked="form.remember" name="remember" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                </label>
            </div>


            <div class="items-center mt-4">
                <button

                    type="button"
                    @click="openQrModal"
                    class="mr-4 underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                >
                    Войти по QR-коду
                </button>

                <Link v-if="canResetPassword" :href="route('password.request')" class="mr-4 underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                    Forgot your password?
                </Link>

                <Link :href="route('register')" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                    Don't have an account?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>

    <Modal :show="showQrModal" max-width="sm" @close="closeQrModal">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Вход по QR-коду
            </h2>

            <div class="flex flex-col items-center py-6">
                <img v-if="qrImage" :src="qrImage" class="w-56 h-56" alt="QR login" />
                <div v-else class="w-56 h-56 flex items-center justify-center text-gray-400 text-sm">
                    Генерация QR-кода...
                </div>
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400 text-center">
                    Отсканируйте код с телефона на сайте для входа
                </p>
            </div>
            <div class="flex justify-center text-black dark:text-white">
                Осталось времени: {{ formattedTime }}
            </div>
            <div class="flex justify-end">
                <SecondaryButton @click="closeQrModal">
                    Закрыть
                </SecondaryButton>
            </div>
        </div>
    </Modal>

</template>
