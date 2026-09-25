<script setup lang="ts">
import { redirect as googleRedirect } from '@/routes/google';
</script>

<template>
    <div class="relative w-full">
        <a
            :href="googleRedirect.url()"
            class="gap-space-md bg-surface-container-lowest px-space-lg hover:bg-surface-container-low flex h-11 w-full items-center justify-center rounded-lg shadow-sm transition-all duration-150 active:scale-[0.99]"
        >
            <svg
                class="h-4 w-4 shrink-0"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path
                    d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.66-5.17 3.66-9.17z"
                    fill="#4285F4"
                />
                <path
                    d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.34 24 12 24z"
                    fill="#34A853"
                />
                <path
                    d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.14-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.99 0 12s.45 3.82 1.25 5.42l4.03-3.15z"
                    fill="#FBBC05"
                />
                <path
                    d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.34 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"
                    fill="#EA4335"
                />
            </svg>
            <span class="text-label-md text-on-surface"
                >Continuar con Google</span
            >
        </a>
        <div
            class="bg-surface-container-high pointer-events-none absolute -top-2.5 right-3 flex items-center gap-1 rounded-full px-2 py-0.5 shadow-sm"
        >
            <span class="bg-primary-container h-1.5 w-1.5 rounded-full" />
            <span class="text-label-xs text-primary">Recomendado</span>
        </div>
    </div>
</template>
