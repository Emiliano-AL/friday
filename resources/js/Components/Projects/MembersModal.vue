<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
import Modal from '@/Components/Modal.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import {
    destroy as destroyInvitation,
    resend,
} from '@/routes/projects/invitations';
import { destroy, store } from '@/routes/projects/members';
import type {
    PendingInvitation,
    ProjectDetail,
    ProjectMember,
} from '@/types/project';
import { relativeTime } from '@/utils/relativeTime';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    project: ProjectDetail;
}>();

const emit = defineEmits<{
    close: [];
}>();

const emailInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    email: '',
});

const membersCount = computed(() => 1 + props.project.members.length);

const page = usePage<{ flash: { success: string | null; error: string | null } }>();

const busyInvitationId = ref<number | null>(null);

function resendInvitation(invitation: PendingInvitation): void {
    router.post(
        resend.url([props.project.id, invitation.id]),
        {},
        {
            preserveScroll: true,
            onStart: () => (busyInvitationId.value = invitation.id),
            onFinish: () => (busyInvitationId.value = null),
        },
    );
}

function cancelInvitation(invitation: PendingInvitation): void {
    if (!window.confirm(`¿Cancelar la invitación a ${invitation.email}?`)) {
        return;
    }

    router.delete(destroyInvitation.url([props.project.id, invitation.id]), {
        preserveScroll: true,
        onStart: () => (busyInvitationId.value = invitation.id),
        onFinish: () => (busyInvitationId.value = null),
    });
}

watch(
    () => props.open,
    async (open) => {
        if (open) {
            form.reset();
            form.clearErrors();
            await nextTick();
            emailInput.value?.focus();
        }
    },
);

function submit(): void {
    form.post(store.url(props.project.id), {
        preserveScroll: true,
        onSuccess: () => form.reset('email'),
    });
}

function removeMember(member: ProjectMember): void {
    const message =
        member.openTasksCount > 0
            ? `¿Retirar a ${member.name} del proyecto? Tiene ${member.openTasksCount} tareas abiertas en el proyecto que quedarán sin responsable.`
            : `¿Retirar a ${member.name} del proyecto?`;

    if (window.confirm(message)) {
        router.delete(destroy.url([props.project.id, member.id]), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Modal :show="open" max-width="2xl" @close="emit('close')">
        <div class="gap-space-lg p-space-lg flex flex-col">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2
                        class="text-headline-sm font-headline-sm text-on-surface"
                    >
                        Gestionar colaboradores
                    </h2>
                    <p
                        class="text-body-sm font-body-sm text-on-surface-variant mt-1"
                    >
                        Controla quién tiene acceso en {{ project.title }}
                    </p>
                </div>
                <button
                    type="button"
                    aria-label="Cerrar modal"
                    class="text-on-surface-variant hover:bg-surface-container hover:text-on-surface flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
                    @click="emit('close')"
                >
                    <AppIcon name="close" :size="18" />
                </button>
            </div>

            <form class="flex flex-col gap-2" @submit.prevent="submit">
                <label
                    for="member-email"
                    class="text-label-sm font-label-sm text-on-surface font-medium"
                >
                    Agregar colaborador
                </label>
                <div class="gap-space-sm flex items-center">
                    <input
                        id="member-email"
                        ref="emailInput"
                        v-model="form.email"
                        type="email"
                        required
                        placeholder="correo@empresa.com"
                        :class="form.errors.email ? 'border-error' : ''"
                        class="text-body-sm font-body-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:ring-primary h-10 w-full rounded-lg border px-3 shadow-xs focus:ring-2 focus:outline-none"
                    />
                    <button
                        type="submit"
                        :disabled="form.processing"
                        :class="{ 'opacity-60': form.processing }"
                        class="bg-primary-container hover:bg-primary shadow-primary-container/30 font-label-md text-label-md h-10 shrink-0 rounded-lg px-4 text-white shadow-md transition-colors"
                    >
                        Agregar
                    </button>
                </div>
                <p
                    v-if="form.errors.email"
                    class="text-label-xs font-label-xs text-error"
                >
                    {{ form.errors.email }}
                </p>
                <p
                    v-else-if="page.props.flash.error"
                    class="text-label-xs font-label-xs text-error"
                    role="alert"
                >
                    {{ page.props.flash.error }}
                </p>
                <p
                    v-else-if="page.props.flash.success"
                    class="text-label-xs font-label-xs text-primary"
                    role="status"
                >
                    {{ page.props.flash.success }}
                </p>
                <p
                    v-else
                    class="text-label-xs font-label-xs text-on-surface-variant"
                >
                    Si el correo no tiene cuenta en Friday, le enviaremos una
                    invitación por correo.
                </p>
            </form>

            <section
                v-if="project.pendingInvitations.length > 0"
                aria-label="Invitaciones pendientes"
                class="gap-space-sm flex flex-col"
            >
                <p
                    class="text-label-sm font-label-sm text-on-surface font-medium"
                >
                    Invitaciones pendientes ({{
                        project.pendingInvitations.length
                    }})
                </p>
                <ul class="gap-space-xs flex flex-col">
                    <li
                        v-for="invitation in project.pendingInvitations"
                        :key="invitation.id"
                        class="gap-space-sm p-space-sm bg-surface-container-low/50 flex items-center rounded-lg"
                    >
                        <span
                            class="border-outline-variant text-on-surface-variant flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-dashed"
                            aria-hidden="true"
                        >
                            <AppIcon name="mail" :size="16" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p
                                class="text-body-sm font-body-sm text-on-surface truncate font-medium"
                            >
                                {{ invitation.email }}
                            </p>
                            <p
                                class="text-label-xs font-label-xs text-on-surface-variant flex items-center gap-1.5"
                            >
                                Enviada {{ relativeTime(invitation.sentAt) }}
                                <UiBadge
                                    v-if="invitation.isExpired"
                                    label="Vencida"
                                    tone="error"
                                />
                            </p>
                        </div>
                        <button
                            type="button"
                            :disabled="busyInvitationId === invitation.id"
                            :aria-label="`Reenviar invitación a ${invitation.email}`"
                            title="Reenviar"
                            class="text-on-surface-variant hover:bg-surface-container hover:text-on-surface flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors disabled:opacity-50"
                            @click="resendInvitation(invitation)"
                        >
                            <AppIcon name="send" :size="16" />
                        </button>
                        <button
                            type="button"
                            :disabled="busyInvitationId === invitation.id"
                            :aria-label="`Cancelar invitación a ${invitation.email}`"
                            title="Cancelar invitación"
                            class="text-on-surface-variant hover:bg-error-container/50 hover:text-error flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors disabled:opacity-50"
                            @click="cancelInvitation(invitation)"
                        >
                            <AppIcon name="close" :size="16" />
                        </button>
                    </li>
                </ul>
            </section>

            <div
                class="border-outline-variant/40 bg-surface-container-low/50 gap-space-sm p-space-sm flex flex-col rounded-lg border"
                aria-disabled="true"
            >
                <div class="flex items-center justify-between gap-3">
                    <span
                        class="text-label-sm font-label-sm text-on-surface flex items-center gap-1.5 font-medium"
                    >
                        <AppIcon name="link" :size="15" />
                        Enlace de invitación rápida
                    </span>
                    <UiBadge label="Próximamente" tone="outline" />
                </div>
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <AppIcon
                            name="lock"
                            :size="14"
                            class="text-on-surface-variant shrink-0"
                        />
                        <p
                            class="text-label-xs font-label-xs text-on-surface-variant"
                        >
                            Cualquiera con el enlace de la organización podría
                            unirse como colaborador.
                        </p>
                    </div>
                    <button
                        type="button"
                        disabled
                        title="Próximamente"
                        class="border-outline-variant/40 bg-surface-container-lowest text-on-surface-variant h-8 shrink-0 cursor-not-allowed rounded-lg border px-3 text-xs opacity-60"
                    >
                        Copiar enlace
                    </button>
                </div>
            </div>

            <div class="gap-space-sm flex flex-col">
                <div class="flex items-baseline justify-between gap-3">
                    <p
                        class="text-label-sm font-label-sm text-on-surface font-medium"
                    >
                        Miembros actuales ({{ membersCount }})
                    </p>
                    <p
                        class="text-label-xs font-label-xs text-on-surface-variant hidden sm:block"
                    >
                        Permisos del proyecto
                    </p>
                </div>
                <ul class="gap-space-xs flex flex-col">
                    <li
                        class="gap-space-sm p-space-sm bg-surface-container-low/50 flex items-center rounded-lg"
                    >
                        <Avatar
                            :name="project.owner.name"
                            :src="project.owner.avatar"
                            :size="32"
                        />
                        <div class="min-w-0 flex-1">
                            <p
                                class="text-body-sm font-body-sm text-on-surface flex min-w-0 items-center gap-2"
                            >
                                <span class="truncate font-medium">
                                    {{ project.owner.name }}
                                </span>
                                <UiBadge label="Propietario" tone="neutral" />
                            </p>
                            <p
                                class="text-label-xs font-label-xs text-on-surface-variant truncate"
                            >
                                {{ project.owner.email }}
                                <template
                                    v-if="project.owner.openTasksCount > 0"
                                >
                                    · {{ project.owner.openTasksCount }} tareas
                                    abiertas
                                </template>
                            </p>
                        </div>
                    </li>
                    <li
                        v-for="member in project.members"
                        :key="member.id"
                        class="gap-space-sm p-space-sm bg-surface-container-low/50 flex items-center rounded-lg"
                    >
                        <Avatar
                            :name="member.name"
                            :src="member.avatar"
                            :size="32"
                        />
                        <div class="min-w-0 flex-1">
                            <p
                                class="text-body-sm font-body-sm text-on-surface truncate font-medium"
                            >
                                {{ member.name }}
                            </p>
                            <p
                                class="text-label-xs font-label-xs text-on-surface-variant truncate"
                            >
                                {{ member.email }}
                                <template v-if="member.openTasksCount > 0">
                                    · {{ member.openTasksCount }} tareas
                                    abiertas
                                </template>
                            </p>
                        </div>
                        <div
                            class="flex shrink-0 items-center gap-1"
                            aria-disabled="true"
                            title="Permisos diferenciados: próximamente"
                        >
                            <AppIcon
                                name="lock"
                                :size="13"
                                class="text-on-surface-variant shrink-0"
                            />
                            <select
                                disabled
                                aria-label="Permisos del proyecto"
                                class="border-outline-variant/40 bg-surface-container-low text-on-surface-variant h-8 cursor-not-allowed rounded-lg border px-2 text-xs opacity-60"
                            >
                                <option>Editor</option>
                            </select>
                            <span class="hidden md:inline">
                                <UiBadge label="Próximamente" tone="outline" />
                            </span>
                        </div>
                        <button
                            v-if="project.isOwner"
                            type="button"
                            :aria-label="`Retirar a ${member.name}`"
                            class="text-on-surface-variant hover:text-error hover:bg-surface-container flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors"
                            @click="removeMember(member)"
                        >
                            <AppIcon name="person_remove" :size="18" />
                        </button>
                    </li>
                    <li
                        v-if="project.members.length === 0"
                        class="text-body-sm font-body-sm text-on-surface-variant"
                    >
                        Sin colaboradores todavía.
                    </li>
                </ul>
            </div>

            <div class="flex items-center justify-between gap-3">
                <p
                    class="text-label-xs font-label-xs text-on-surface-variant"
                >
                    Los colaboradores podrán recibir tareas asignadas de este
                    proyecto.
                </p>
                <button
                    type="button"
                    class="bg-primary-container hover:bg-primary shadow-primary-container/30 font-label-md text-label-md shrink-0 rounded-lg px-6 py-2 text-white shadow-md transition-colors"
                    @click="emit('close')"
                >
                    Listo
                </button>
            </div>
        </div>
    </Modal>
</template>
