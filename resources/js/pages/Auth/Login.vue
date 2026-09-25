<script setup lang="ts">
import AuthCard from '@/Components/Auth/AuthCard.vue';
import AuthCheckbox from '@/Components/Auth/AuthCheckbox.vue';
import AuthIcon from '@/Components/Auth/AuthIcon.vue';
import AuthTextInput from '@/Components/Auth/AuthTextInput.vue';
import SocialGoogleButton from '@/Components/Auth/SocialGoogleButton.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { login, register } from '@/routes';
import { request as passwordRequest } from '@/routes/password';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(login.url(), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesión" />

        <AuthCard
            title="Iniciar sesión en Friday"
            subtitle="Tu espacio de trabajo ágil y productivo"
        >
            <SocialGoogleButton />

            <div class="my-space-xs gap-space-md flex items-center">
                <div class="bg-surface-container-high h-px flex-1" />
                <span
                    class="text-label-xs text-on-surface-variant tracking-wider uppercase"
                >
                    o continuar con correo
                </span>
                <div class="bg-surface-container-high h-px flex-1" />
            </div>

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
                    label="Correo electrónico de trabajo"
                    placeholder="nombre@empresa.com"
                    autocomplete="username"
                    required
                    autofocus
                    :error="form.errors.email"
                />

                <AuthTextInput
                    id="password"
                    v-model="form.password"
                    icon="lock"
                    :type="showPassword ? 'text' : 'password'"
                    label="Contraseña"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required
                    :error="form.errors.password"
                >
                    <template #label-trailing>
                        <Link
                            v-if="canResetPassword"
                            :href="passwordRequest.url()"
                            class="text-label-xs text-primary-container inline-flex min-h-6 items-center transition-all hover:underline"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    </template>
                    <template #trailing>
                        <button
                            type="button"
                            :aria-pressed="showPassword"
                            aria-label="Alternar visibilidad de contraseña"
                            class="mr-space-xs p-space-sm text-on-surface-variant hover:text-on-surface flex items-center justify-center rounded transition-colors"
                            @click="showPassword = !showPassword"
                        >
                            <AuthIcon
                                :name="showPassword ? 'eye-off' : 'eye'"
                                :size="18"
                            />
                        </button>
                    </template>
                </AuthTextInput>

                <div class="pt-space-2xs flex items-center justify-between">
                    <AuthCheckbox
                        id="remember"
                        v-model="form.remember"
                        label="Recordar este dispositivo"
                    />
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="group mt-space-xs gap-space-sm bg-primary-container text-label-md text-on-primary shadow-primary-container/20 hover:bg-primary flex h-11 w-full items-center justify-center rounded-lg shadow-md transition-all duration-150 active:scale-[0.99] disabled:opacity-25"
                >
                    <span>Iniciar sesión</span>
                    <AuthIcon
                        name="arrow-right"
                        :size="16"
                        class="transition-transform group-hover:translate-x-0.5"
                    />
                    <kbd
                        class="ml-space-xs bg-on-primary/20 text-label-xs hidden items-center justify-center rounded px-1.5 py-0.5 sm:inline-flex"
                    >
                        ↵
                    </kbd>
                </button>
            </form>

            <p class="text-body-sm text-on-surface-variant text-center">
                ¿No tienes cuenta de equipo?
                <Link
                    :href="register.url()"
                    class="text-label-sm text-primary-container ml-1 inline-flex min-h-6 items-center hover:underline"
                >
                    Regístrate gratis
                </Link>
            </p>
        </AuthCard>
    </GuestLayout>
</template>
