<script setup lang="ts">
import AuthCard from '@/Components/Auth/AuthCard.vue';
import AuthIcon from '@/Components/Auth/AuthIcon.vue';
import AuthTextInput from '@/Components/Auth/AuthTextInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { login } from '@/routes';
import { email as passwordEmail } from '@/routes/password';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(passwordEmail.url());
};
</script>

<template>
    <GuestLayout>
        <Head title="Recuperar contraseña" />

        <AuthCard
            title="Recuperar contraseña"
            subtitle="Te enviaremos un enlace a tu correo para restablecer tu contraseña."
        >
            <div
                v-if="status"
                class="rounded-lg bg-primary-fixed/40 px-space-md py-space-sm text-body-sm text-primary-container"
            >
                {{ status }}
            </div>

            <form class="flex flex-col gap-space-md" @submit.prevent="submit">
                <AuthTextInput
                    id="email"
                    v-model="form.email"
                    icon="mail"
                    type="email"
                    label="Correo electrónico"
                    placeholder="nombre@empresa.com"
                    autocomplete="username"
                    required
                    autofocus
                    :error="form.errors.email"
                />

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="group mt-space-xs flex h-11 w-full items-center justify-center gap-space-sm rounded-lg bg-primary-container text-label-md text-on-primary shadow-md shadow-primary-container/20 transition-all duration-150 hover:bg-primary active:scale-[0.99] disabled:opacity-25"
                >
                    <span>Enviar enlace de recuperación</span>
                    <AuthIcon
                        name="arrow-right"
                        :size="16"
                        class="transition-transform group-hover:translate-x-0.5"
                    />
                </button>
            </form>

            <p class="text-center text-body-sm text-on-surface-variant">
                <Link
                    :href="login.url()"
                    class="inline-flex min-h-6 items-center text-label-sm text-primary-container hover:underline"
                >
                    Volver a iniciar sesión
                </Link>
            </p>
        </AuthCard>
    </GuestLayout>
</template>
