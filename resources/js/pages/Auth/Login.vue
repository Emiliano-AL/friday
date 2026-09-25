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

            <div class="my-space-xs flex items-center gap-space-md">
                <div class="h-px flex-1 bg-surface-container-high" />
                <span
                    class="text-label-xs uppercase tracking-wider text-on-surface-variant"
                >
                    o continuar con correo
                </span>
                <div class="h-px flex-1 bg-surface-container-high" />
            </div>

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
                            class="inline-flex min-h-6 items-center text-label-xs text-primary-container transition-all hover:underline"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    </template>
                    <template #trailing>
                        <button
                            type="button"
                            :aria-pressed="showPassword"
                            aria-label="Alternar visibilidad de contraseña"
                            class="mr-space-xs flex items-center justify-center rounded p-space-sm text-on-surface-variant transition-colors hover:text-on-surface"
                            @click="showPassword = !showPassword"
                        >
                            <AuthIcon
                                :name="showPassword ? 'eye-off' : 'eye'"
                                :size="18"
                            />
                        </button>
                    </template>
                </AuthTextInput>

                <div class="flex items-center justify-between pt-space-2xs">
                    <AuthCheckbox
                        id="remember"
                        v-model="form.remember"
                        label="Recordar este dispositivo"
                    />
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="group mt-space-xs flex h-11 w-full items-center justify-center gap-space-sm rounded-lg bg-primary-container text-label-md text-on-primary shadow-md shadow-primary-container/20 transition-all duration-150 hover:bg-primary active:scale-[0.99] disabled:opacity-25"
                >
                    <span>Iniciar sesión</span>
                    <AuthIcon
                        name="arrow-right"
                        :size="16"
                        class="transition-transform group-hover:translate-x-0.5"
                    />
                    <kbd
                        class="ml-space-xs hidden items-center justify-center rounded bg-on-primary/20 px-1.5 py-0.5 text-label-xs sm:inline-flex"
                    >
                        ↵
                    </kbd>
                </button>
            </form>

            <p class="text-center text-body-sm text-on-surface-variant">
                ¿No tienes cuenta de equipo?
                <Link
                    :href="register.url()"
                    class="ml-1 inline-flex min-h-6 items-center text-label-sm text-primary-container hover:underline"
                >
                    Regístrate gratis
                </Link>
            </p>
        </AuthCard>
    </GuestLayout>
</template>
