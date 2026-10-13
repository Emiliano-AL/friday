<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
import Modal from '@/Components/Modal.vue';
import { show as projectShow } from '@/routes/projects';
import { destroy as settingsDestroy } from '@/routes/settings';
import { update as settingsPasswordUpdate } from '@/routes/settings/password';
import { update as settingsProfileUpdate } from '@/routes/settings/profile';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    account: {
        name: string;
        email: string;
        avatar: string | null;
        hasPassword: boolean;
        usesGoogle: boolean;
    };
    ownedProjects: { id: number; title: string; color: string | null }[];
}>();

const inputClass =
    'border-outline-variant/60 bg-surface-container-lowest text-body-md font-body-md text-on-surface placeholder:text-on-surface-variant/50 focus:border-primary focus:ring-primary/20 h-9 w-full rounded-lg border px-3 transition-colors focus:ring-2 focus:outline-none';
const errorRing = 'border-error ring-error/20 ring-2';
const primaryButton =
    'bg-primary hover:bg-primary-container text-label-md font-label-md text-on-primary shadow-card rounded-lg px-4 py-2 transition-colors disabled:opacity-50';

const accessMethod = computed(() => {
    const methods = [
        props.account.hasPassword ? 'correo y contraseña' : null,
        props.account.usesGoogle ? 'Google' : null,
    ].filter(Boolean);

    return methods.join(' y ') || '—';
});

const profileForm = useForm({ name: props.account.name });

function saveProfile(): void {
    profileForm.patch(settingsProfileUpdate.url(), { preserveScroll: true });
}

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function savePassword(): void {
    passwordForm.put(settingsPasswordUpdate.url(), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () =>
            passwordForm.reset(
                'current_password',
                'password',
                'password_confirmation',
            ),
    });
}

const confirmingDelete = ref(false);
const deleteField = computed(() =>
    props.account.hasPassword ? 'password' : 'confirmation',
);
const deleteForm = useForm({ password: '', confirmation: '' });

function closeDelete(): void {
    confirmingDelete.value = false;
    deleteForm.reset();
    deleteForm.clearErrors();
}

function deleteAccount(): void {
    deleteForm.delete(settingsDestroy.url(), {
        preserveScroll: true,
        onError: () => deleteForm.reset(),
    });
}
</script>

<template>
    <Head title="Configuración" />

    <AuthenticatedLayout>
        <div
            class="gap-space-lg py-space-md mx-auto flex w-full max-w-3xl flex-col"
        >
            <header class="gap-space-xs flex flex-col">
                <h1 class="text-headline-xl font-headline-xl text-on-surface">
                    Configuración
                </h1>
                <p class="text-body-md font-body-md text-on-surface-variant">
                    Tu perfil y la seguridad de tu cuenta.
                </p>
            </header>

            <section
                id="perfil"
                class="bg-surface-container-lowest shadow-card gap-space-md p-space-lg flex scroll-mt-24 flex-col rounded-xl"
            >
                <div class="gap-space-md flex items-center">
                    <Avatar
                        :name="account.name"
                        :src="account.avatar"
                        :size="48"
                    />
                    <div class="min-w-0">
                        <h2
                            class="text-headline-sm font-headline-sm text-on-surface"
                        >
                            Perfil
                        </h2>
                        <p
                            class="text-body-sm font-body-sm text-on-surface-variant"
                        >
                            Entras con {{ accessMethod }}.
                        </p>
                    </div>
                </div>

                <form
                    class="gap-space-md flex flex-col"
                    @submit.prevent="saveProfile"
                >
                    <div class="flex flex-col gap-1.5">
                        <label
                            for="settings-name"
                            class="text-label-sm font-label-sm text-on-surface"
                        >
                            Nombre
                        </label>
                        <input
                            id="settings-name"
                            v-model="profileForm.name"
                            type="text"
                            required
                            maxlength="255"
                            autocomplete="name"
                            :class="[
                                inputClass,
                                profileForm.errors.name ? errorRing : '',
                            ]"
                        />
                        <p
                            v-if="profileForm.errors.name"
                            class="text-label-xs font-label-xs text-error"
                        >
                            {{ profileForm.errors.name }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <span
                            class="text-label-sm font-label-sm text-on-surface"
                        >
                            Correo
                        </span>
                        <p
                            class="text-body-md font-body-md text-on-surface bg-surface-container-low flex h-9 items-center gap-2 rounded-lg px-3"
                        >
                            <AppIcon
                                name="lock"
                                :size="16"
                                class="text-on-surface-variant"
                            />
                            <span class="truncate">{{ account.email }}</span>
                        </p>
                        <p class="text-label-xs text-on-surface-variant">
                            Para cambiar tu correo, contacta a un administrador.
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="
                                profileForm.processing || !profileForm.isDirty
                            "
                            :class="primaryButton"
                        >
                            Guardar perfil
                        </button>
                    </div>
                </form>
            </section>

            <section
                id="seguridad"
                class="bg-surface-container-lowest shadow-card gap-space-md p-space-lg flex scroll-mt-24 flex-col rounded-xl"
            >
                <div>
                    <h2
                        class="text-headline-sm font-headline-sm text-on-surface"
                    >
                        {{
                            account.hasPassword
                                ? 'Cambiar contraseña'
                                : 'Definir contraseña'
                        }}
                    </h2>
                    <p
                        class="text-body-sm font-body-sm text-on-surface-variant"
                    >
                        {{
                            account.hasPassword
                                ? 'Al cambiarla se cerrará tu sesión en los demás dispositivos.'
                                : 'Entras con Google. Define una contraseña para entrar también con tu correo.'
                        }}
                    </p>
                </div>

                <form
                    class="gap-space-md flex flex-col"
                    @submit.prevent="savePassword"
                >
                    <div
                        v-if="account.hasPassword"
                        class="flex flex-col gap-1.5"
                    >
                        <label
                            for="settings-current-password"
                            class="text-label-sm font-label-sm text-on-surface"
                        >
                            Contraseña actual
                        </label>
                        <input
                            id="settings-current-password"
                            v-model="passwordForm.current_password"
                            type="password"
                            required
                            autocomplete="current-password"
                            :class="[
                                inputClass,
                                passwordForm.errors.current_password
                                    ? errorRing
                                    : '',
                            ]"
                        />
                        <p
                            v-if="passwordForm.errors.current_password"
                            class="text-label-xs font-label-xs text-error"
                        >
                            {{ passwordForm.errors.current_password }}
                        </p>
                    </div>

                    <div class="gap-space-md grid sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <label
                                for="settings-password"
                                class="text-label-sm font-label-sm text-on-surface"
                            >
                                Nueva contraseña
                            </label>
                            <input
                                id="settings-password"
                                v-model="passwordForm.password"
                                type="password"
                                required
                                minlength="8"
                                autocomplete="new-password"
                                :class="[
                                    inputClass,
                                    passwordForm.errors.password
                                        ? errorRing
                                        : '',
                                ]"
                            />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label
                                for="settings-password-confirmation"
                                class="text-label-sm font-label-sm text-on-surface"
                            >
                                Confirmar contraseña
                            </label>
                            <input
                                id="settings-password-confirmation"
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                :class="inputClass"
                            />
                        </div>
                        <p
                            v-if="passwordForm.errors.password"
                            class="text-label-xs font-label-xs text-error sm:col-span-2"
                        >
                            {{ passwordForm.errors.password }}
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            :class="primaryButton"
                        >
                            {{
                                account.hasPassword
                                    ? 'Cambiar contraseña'
                                    : 'Definir contraseña'
                            }}
                        </button>
                    </div>
                </form>
            </section>

            <section
                id="cuenta"
                class="border-error/30 gap-space-md p-space-lg flex scroll-mt-24 flex-col rounded-xl border"
            >
                <div>
                    <h2 class="text-headline-sm font-headline-sm text-error">
                        Eliminar cuenta
                    </h2>
                    <p
                        class="text-body-sm font-body-sm text-on-surface-variant"
                    >
                        Se borran tu cuenta, tus tareas sueltas y tus
                        comentarios. No se puede deshacer.
                    </p>
                </div>

                <div
                    v-if="ownedProjects.length > 0"
                    class="bg-surface-container-low gap-space-sm p-space-md flex flex-col rounded-lg"
                >
                    <p class="text-body-sm font-body-sm text-on-surface">
                        {{
                            ownedProjects.length === 1
                                ? 'Tienes 1 proyecto propio. Elimínalo antes de eliminar tu cuenta.'
                                : `Tienes ${ownedProjects.length} proyectos propios. Elimínalos antes de eliminar tu cuenta.`
                        }}
                    </p>
                    <ul class="flex flex-col gap-1">
                        <li v-for="project in ownedProjects" :key="project.id">
                            <Link
                                :href="projectShow.url(project.id)"
                                class="text-label-md font-label-md text-primary inline-flex items-center gap-2 hover:underline"
                            >
                                <span
                                    class="h-2 w-2 shrink-0 rounded-full"
                                    :style="{
                                        backgroundColor:
                                            project.color ??
                                            'var(--color-outline)',
                                    }"
                                />
                                {{ project.title }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <div class="flex justify-end">
                    <button
                        type="button"
                        :disabled="ownedProjects.length > 0"
                        class="bg-error text-on-error text-label-md font-label-md rounded-lg px-4 py-2 transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
                        @click="confirmingDelete = true"
                    >
                        Eliminar cuenta
                    </button>
                </div>
            </section>
        </div>

        <Modal :show="confirmingDelete" max-width="md" @close="closeDelete">
            <form
                class="gap-space-md p-space-lg flex flex-col"
                @submit.prevent="deleteAccount"
            >
                <div>
                    <h3
                        class="text-headline-sm font-headline-sm text-on-surface"
                    >
                        ¿Eliminar tu cuenta?
                    </h3>
                    <p
                        class="text-body-sm font-body-sm text-on-surface-variant"
                    >
                        {{
                            account.hasPassword
                                ? 'Escribe tu contraseña para confirmar.'
                                : 'Escribe tu correo para confirmar.'
                        }}
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <input
                        v-if="account.hasPassword"
                        v-model="deleteForm.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        aria-label="Contraseña"
                        :class="[
                            inputClass,
                            deleteForm.errors.password ? errorRing : '',
                        ]"
                    />
                    <input
                        v-else
                        v-model="deleteForm.confirmation"
                        type="email"
                        required
                        aria-label="Correo"
                        :placeholder="account.email"
                        :class="[
                            inputClass,
                            deleteForm.errors.confirmation ? errorRing : '',
                        ]"
                    />
                    <p
                        v-if="deleteForm.errors[deleteField]"
                        class="text-label-xs font-label-xs text-error"
                    >
                        {{ deleteForm.errors[deleteField] }}
                    </p>
                </div>

                <div class="gap-space-sm flex justify-end">
                    <button
                        type="button"
                        class="text-label-md font-label-md text-on-surface-variant hover:bg-surface-container hover:text-on-surface rounded-lg px-4 py-2 transition-colors"
                        @click="closeDelete"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="deleteForm.processing"
                        class="bg-error text-on-error text-label-md font-label-md rounded-lg px-4 py-2 transition-opacity hover:opacity-90 disabled:opacity-50"
                    >
                        Eliminar definitivamente
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
