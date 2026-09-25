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
                class="bg-primary-fixed/40 px-space-md py-space-sm text-body-sm text-primary-container rounded-lg"
            >
                {{ status }}
            </div>

            <form class="gap-space-md flex flex-col" @submit.prevent="submit">
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
                    class="group mt-space-xs gap-space-sm bg-primary-container text-label-md text-on-primary shadow-primary-container/20 hover:bg-primary flex h-11 w-full items-center justify-center rounded-lg shadow-md transition-all duration-150 active:scale-[0.99] disabled:opacity-25"
                >
                    <span>Enviar enlace de recuperación</span>
                    <AuthIcon
                        name="arrow-right"
                        :size="16"
                        class="transition-transform group-hover:translate-x-0.5"
                    />
                </button>
            </form>

            <p class="text-body-sm text-on-surface-variant text-center">
                <Link
                    :href="login.url()"
                    class="text-label-sm text-primary-container inline-flex min-h-6 items-center hover:underline"
                >
                    Volver a iniciar sesión
                </Link>
            </p>
        </AuthCard>
    </GuestLayout>
</template>
