<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import CardComponent from "@/Pages/Auth/CardComponent.vue";
import {onMounted, ref} from "vue";
import axios from "axios";
import DangerButton from "@/Components/DangerButton.vue";
import {UAParser} from 'ua-parser-js';

const props = defineProps({
    uuid: String,
})

async function get_info_from_uuid () {
    try {
        const response = await axios.get(`/api/qr/session/${props.uuid}/info`);

        return response.data;
    } catch(error) {
        return {
            message: "Can't get information about this uuid",
            error: error.message,
        };
    }
}


const userdevice = ref('desktop') // безопасное значение по умолчанию
const info = ref(null) // безопасное значение по умолчанию

onMounted(async () => {
    try {
        const response = await axios.get('/api/deviceinfo')
        userdevice.value = response.data.device

        info.value = await get_info_from_uuid()
        const parser = new UAParser(info.value.user_agent);
        const result = parser.getResult();

        userIp.value = info.value.ip_address
        userBrowser.value = `${result.browser.name ?? ''} ${result.browser.major ?? 'unknown'}`
        userOS.value = result.os.name ?? 'unknown'
    } catch (error) {
        console.error('Не удалось определить устройство:', error)
        // device остаётся 'desktop' по умолчанию — страница не виснет
    }
})

const approve = async () => {
    try {
    const res = await axios.post(`/qr/approve/${props.uuid}`, {}, {
        withCredentials: true,
    })
    window.location.href = '/dashboard'
} catch (e) {
        console.error('approve failed:', e.response?.status, e.response?.data)
        alert('Ошибка: ' + (e.response?.status ?? e.message ?? e.config.url))
    }
}
const toDashboardAndDeleteQrSession = async () => {
    try {
        // await axios.delete(`/api/qr/session/${props.uuid}`)
        await axios.post(`/qr/reject/${props.uuid}`)
    } catch (error) {
        console.error('Не удалось отклонить QR-сессию: ', error)
    }
    window.location.href = '/dashboard'
    }

let userBrowser = ref('unknown')
let userOS = ref('unknown')
let userIp = ref('unknown')
</script>

<template>
    <CardComponent title="Approve login page">
        <template #title>
            <div class="flex justify-center">
                <h2 class="text-2xl text-black text-bold dark:text-white">Подтверждение входа</h2>
            </div>
        </template>
        <template #body>
            <div class="flex justify-start text-xl text-black dark:text-white">
                Вы уверены что хотите разрешить вход данному устройству?<br>
                IP: {{ userIp }}(пока что отображается только ваш IP)<br>
                OS: {{ userOS }}<br>
                Browser: {{ userBrowser }}
            </div>
        </template>
        <template #footer>
            <div class="flex justify-center gap-6">
                <PrimaryButton
                    @click="approve"
                >
                    Подтвердить вход
                </PrimaryButton>
                <DangerButton
                    @click="toDashboardAndDeleteQrSession"
                >
                        Отклонить вход
                </DangerButton>
            </div>
        </template>
    </CardComponent>
</template>
