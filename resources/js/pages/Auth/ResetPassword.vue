<script setup lang="ts">
import AuthCard from '@/Components/Auth/AuthCard.vue';
import AuthIcon from '@/Components/Auth/AuthIcon.vue';
import AuthTextInput from '@/Components/Auth/AuthTextInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { login } from '@/routes';
import { store as passwordStore } from '@/routes/password';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.post(passwordStore.url(), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Restablecer contraseña" />

        <AuthCard
            title="Nueva contraseña"
            subtitle="Elige una contraseña segura para tu cuenta de Friday."
        >
            <form class="flex flex-col gap-space-md" @submit.prevent="submit">
                <input type="hidden" name="email" :value="form.email" />
                <input type="hidden" name="token" :value="form.token" />

                <AuthTextInput
                    id="password"
                    v-model="form.password"
                    icon="lock"
                    :type="showPassword ? 'text' : 'password'"
                    label="Nueva contraseña"
                    placeholder="••••••••"
                    autocomplete="new-password"
                    required
                    autofocus
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
                    <span>Restablecer contraseña</span>
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
