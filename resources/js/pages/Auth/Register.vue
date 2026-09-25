<script setup lang="ts">
import AuthCard from '@/Components/Auth/AuthCard.vue';
import AuthIcon from '@/Components/Auth/AuthIcon.vue';
import AuthTextInput from '@/Components/Auth/AuthTextInput.vue';
import SocialGoogleButton from '@/Components/Auth/SocialGoogleButton.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { login, register } from '@/routes';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.post(register.url(), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Crear cuenta" />

        <AuthCard
            title="Crear cuenta en Friday"
            subtitle="Empieza a organizar el trabajo de tu equipo"
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

            <form class="flex flex-col gap-space-md" @submit.prevent="submit">
                <AuthTextInput
                    id="name"
                    v-model="form.name"
                    icon="user"
                    type="text"
                    label="Nombre completo"
                    placeholder="Ana García"
                    autocomplete="name"
                    required
                    autofocus
                    :error="form.errors.name"
                />

                <AuthTextInput
                    id="email"
                    v-model="form.email"
                    icon="mail"
                    type="email"
                    label="Correo electrónico de trabajo"
                    placeholder="nombre@empresa.com"
                    autocomplete="username"
                    required
                    :error="form.errors.email"
                />

                <AuthTextInput
                    id="password"
                    v-model="form.password"
                    icon="lock"
                    :type="showPassword ? 'text' : 'password'"
                    label="Contraseña"
                    placeholder="••••••••"
                    autocomplete="new-password"
                    required
                    :error="form.errors.password"
                >
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

                <AuthTextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    icon="lock"
                    :type="showPasswordConfirmation ? 'text' : 'password'"
                    label="Confirmar contraseña"
                    placeholder="••••••••"
                    autocomplete="new-password"
                    required
                    :error="form.errors.password_confirmation"
                >
                    <template #trailing>
                        <button
                            type="button"
                            :aria-pressed="showPasswordConfirmation"
                            aria-label="Alternar visibilidad de contraseña"
                            class="mr-space-xs flex items-center justify-center rounded p-space-sm text-on-surface-variant transition-colors hover:text-on-surface"
                            @click="
                                showPasswordConfirmation = !showPasswordConfirmation
                            "
                        >
                            <AuthIcon
                                :name="showPasswordConfirmation ? 'eye-off' : 'eye'"
                                :size="18"
                            />
                        </button>
                    </template>
                </AuthTextInput>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="group mt-space-xs flex h-11 w-full items-center justify-center gap-space-sm rounded-lg bg-primary-container text-label-md text-on-primary shadow-md shadow-primary-container/20 transition-all duration-150 hover:bg-primary active:scale-[0.99] disabled:opacity-25"
                >
                    <span>Crear cuenta</span>
                    <AuthIcon
                        name="arrow-right"
                        :size="16"
                        class="transition-transform group-hover:translate-x-0.5"
                    />
                </button>
            </form>

            <p class="text-center text-body-sm text-on-surface-variant">
                ¿Ya tienes cuenta?
                <Link
                    :href="login.url()"
                    class="ml-1 inline-flex min-h-6 items-center text-label-sm text-primary-container hover:underline"
                >
                    Inicia sesión
                </Link>
            </p>
        </AuthCard>
    </GuestLayout>
</template>
