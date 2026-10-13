<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import AuthCard from '@/Components/Auth/AuthCard.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { home, login, logout, register } from '@/routes';
import { accept, decline } from '@/routes/invitations';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type InvitationState = 'valid' | 'guest' | 'mismatch' | 'unavailable';
type UnavailableReason =
    | 'expired'
    | 'cancelled'
    | 'used'
    | 'inactive_project'
    | 'not_found';

const props = defineProps<{
    state: InvitationState;
    reason?: UnavailableReason | null;
    currentEmail?: string | null;
    token?: string;
    invitation?: {
        email: string;
        project: { title: string; color: string | null; icon: string | null };
        invitedBy: { name: string; avatar: string | null };
        expiresAt: string;
    };
}>();

const processing = ref(false);

const unavailableMessages: Record<UnavailableReason, string> = {
    expired:
        'Esta invitación venció. Pide al líder del proyecto que te envíe una nueva.',
    cancelled: 'Esta invitación ya no está disponible.',
    used: 'Esta invitación ya fue respondida.',
    inactive_project:
        'El proyecto no está activo, por lo que no se puede aceptar la invitación.',
    not_found: 'No encontramos esta invitación. Revisa que el enlace esté completo.',
};

const title = computed(() =>
    props.state === 'unavailable' || !props.invitation
        ? 'Invitación no disponible'
        : `Colabora en ${props.invitation.project.title}`,
);

const expiresLabel = computed(() =>
    props.invitation
        ? new Date(props.invitation.expiresAt).toLocaleDateString('es-MX', {
              day: 'numeric',
              month: 'long',
          })
        : '',
);

function respond(action: 'accept' | 'decline'): void {
    if (!props.token) {
        return;
    }

    const route = action === 'accept' ? accept : decline;

    router.post(route.url(props.token), {}, {
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
    });
}
</script>

<template>
    <GuestLayout>
        <Head title="Invitación a proyecto" />

        <AuthCard :title="title">
            <template v-if="invitation && state !== 'unavailable'">
                <div class="gap-space-md flex items-center">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl"
                        :class="
                            invitation.project.color
                                ? ''
                                : 'bg-primary/10 text-primary'
                        "
                        :style="
                            invitation.project.color
                                ? {
                                      backgroundColor: `${invitation.project.color}1A`,
                                      color: invitation.project.color,
                                  }
                                : undefined
                        "
                        aria-hidden="true"
                    >
                        <AppIcon
                            :name="invitation.project.icon ?? 'folder'"
                            :size="24"
                        />
                    </div>
                    <p class="text-body-md font-body-md text-on-surface">
                        <span class="font-semibold">{{
                            invitation.invitedBy.name
                        }}</span>
                        te invitó a colaborar en
                        <span class="font-semibold">{{
                            invitation.project.title
                        }}</span
                        >.
                    </p>
                </div>

                <p class="text-body-sm font-body-sm text-on-surface-variant">
                    Invitación para
                    <span class="text-on-surface font-medium">{{
                        invitation.email
                    }}</span>
                    · vence el {{ expiresLabel }}.
                </p>

                <div
                    v-if="state === 'valid'"
                    class="gap-space-sm flex flex-col-reverse sm:flex-row sm:justify-end"
                >
                    <button
                        type="button"
                        :disabled="processing"
                        class="text-label-md font-label-md text-on-surface-variant hover:bg-surface-container hover:text-on-surface rounded-lg px-4 py-2 transition-colors disabled:opacity-50"
                        @click="respond('decline')"
                    >
                        Rechazar
                    </button>
                    <button
                        type="button"
                        :disabled="processing"
                        class="bg-primary hover:bg-primary-container text-on-primary text-label-md font-label-md shadow-card rounded-lg px-4 py-2 transition-colors disabled:opacity-50"
                        @click="respond('accept')"
                    >
                        Aceptar invitación
                    </button>
                </div>

                <div v-else-if="state === 'guest'" class="gap-space-sm flex flex-col">
                    <Link
                        :href="register.url()"
                        class="bg-primary hover:bg-primary-container text-on-primary text-label-md font-label-md shadow-card rounded-lg px-4 py-2.5 text-center transition-colors"
                    >
                        Crear cuenta para aceptar
                    </Link>
                    <Link
                        :href="login.url()"
                        class="border-outline-variant/60 text-on-surface hover:bg-surface-container text-label-md font-label-md rounded-lg border px-4 py-2.5 text-center transition-colors"
                    >
                        Ya tengo cuenta, iniciar sesión
                    </Link>
                    <p
                        class="text-label-sm text-on-surface-variant text-center"
                    >
                        Usa el correo {{ invitation.email }} para poder
                        aceptarla.
                    </p>
                </div>

                <div
                    v-else-if="state === 'mismatch'"
                    class="bg-surface-container-low gap-space-sm p-space-md flex flex-col rounded-lg"
                    role="status"
                >
                    <p class="text-body-sm font-body-sm text-on-surface">
                        Esta invitación es para
                        <span class="font-semibold">{{ invitation.email }}</span>
                        y tu sesión es de
                        <span class="font-semibold">{{ currentEmail }}</span>.
                        Cierra sesión y entra con el correo invitado.
                    </p>
                    <Link
                        :href="logout.url()"
                        method="post"
                        as="button"
                        class="text-label-md font-label-md text-primary hover:bg-primary/10 self-start rounded-lg px-3 py-1.5 transition-colors"
                    >
                        Cerrar sesión
                    </Link>
                </div>
            </template>

            <template v-else>
                <p class="text-body-md font-body-md text-on-surface-variant">
                    {{ unavailableMessages[reason ?? 'not_found'] }}
                </p>
                <Link
                    :href="home.url()"
                    class="text-label-md font-label-md text-primary self-start hover:underline"
                >
                    Ir a Friday
                </Link>
            </template>
        </AuthCard>
    </GuestLayout>
</template>
