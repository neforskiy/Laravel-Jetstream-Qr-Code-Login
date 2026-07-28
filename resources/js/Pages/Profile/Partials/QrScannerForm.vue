<script setup>
import FormSection from "@/Components/FormSection.vue";
import {QrcodeStream} from "vue-qrcode-reader";
import {ref} from "vue";

const result = ref(null)
const error = ref(null)

const onDetect = (codes) => {
    window.location.href = codes[0].rawValue
}

const onError = (err) => {
    error.value = err.message || String(err)
}
</script>

<template>
    <FormSection>
        <template #title>
            Qr scanner
        </template>

        <template #description>
            Scan QR code to log in account on other device.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <qrcode-stream
                    @detect="onDetect"
                    @error="onError"
                />
                <p v-if="result">
                    Результат: {{ result }}
                </p>
                <p v-if="error" style="color: red">
                    Ошибка: {{ error }}
                </p>
            </div>
        </template>
    </FormSection>
</template>
