<script setup>
import { ref, computed, nextTick, onMounted, watch } from "vue";
import { useForm, usePage, Head, router } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Avatar from "primevue/avatar";

const props = defineProps({
    conversations: Object,
    listeConcours: Array,
    isAdmin: Boolean,
});

const user = usePage().props.auth.user;
const selectedContactId = ref(null);
const chatScroll = ref(null);
const localConversations = ref({ ...props.conversations });

watch(
    () => props.conversations,
    (newVal) => {
        localConversations.value = { ...newVal };
        scrollToBottom();
    },
    { deep: true },
);

const form = useForm({
    destinataire_id: null,
    concour_id: null,
    texte: "",
});
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const candidatId = urlParams.get("candidat_id");
    const concourId = urlParams.get("concour_id");

    if (candidatId && concourId) {
        selectedContactId.value = candidatId;

        // PRIORITÉ À L'URL : On remplit le formulaire avec les paramètres reçus
        form.destinataire_id = Number(candidatId);
        form.concour_id = Number(concourId);

        // Initialisation de la vue locale si elle n'existe pas encore
        if (!localConversations.value[candidatId]) {
            localConversations.value[candidatId] = {
                messages: [],
                unread_count: 0,
            };
        }

        // On scrolle après un court délai pour laisser le DOM s'ajuster
        setTimeout(scrollToBottom, 100);
    }
});
const isFromAdmin = (msg) => {
    return (
        msg.emetteur.roles?.some((r) =>
            ["admin", "superadmin"].includes(r.name),
        ) || false
    );
};

const selectConversation = (id) => {
    selectedContactId.value = id;
    const data = localConversations.value[id];

    if (data && data.messages.length > 0) {
        const firstMsg = data.messages[0];
        form.concour_id = Number(firstMsg.concour_id);
        form.destinataire_id = props.isAdmin
            ? Number(id)
            : firstMsg.emetteur_id;

        if (data.unread_count > 0) {
            router.patch(
                route("messagerie.read", id),
                {},
                { preserveScroll: true },
            );
        }
    }
    scrollToBottom();
};

const submit = () => {
    if (!form.texte.trim() || form.processing) return;
    if (!form.concour_id) return alert("Erreur : Aucun concours associé.");

    form.post(route("messagerie.store"), {
        preserveScroll: true,
        onSuccess: () => {
            form.texte = "";
            scrollToBottom();
        },
    });
};

const scrollToBottom = async () => {
    await nextTick();
    if (chatScroll.value)
        chatScroll.value.scrollTop = chatScroll.value.scrollHeight;
};
</script>

<template>
    <AppLayout>
        <div class="messagerie-wrapper card shadow-4">
            <div
                class="sidebar-contacts"
                :class="{ 'hidden-mobile': selectedContactId }"
            >
                <div class="header-sidebar">
                    <h5 class="m-0 font-bold text-primary text-center">
                        MESSAGES
                    </h5>
                </div>
                <div class="contacts-list">
                    <div
                        v-for="(data, id) in localConversations"
                        :key="id"
                        @click="selectConversation(id)"
                        :class="[
                            'contact-item',
                            selectedContactId == id ? 'active-item' : '',
                        ]"
                    >
                        <Avatar
                            icon="pi pi-user"
                            shape="circle"
                            class="bg-blue-100 text-blue-700"
                        />
                        <div class="contact-info">
                            <span class="contact-name">
                                {{
                                    isAdmin
                                        ? (data.messages[0]?.emetteur_id == id
                                              ? data.messages[0]?.emetteur.email
                                              : data.messages[0]?.destinataire
                                                    .email) ||
                                          "Nouveau Candidat"
                                        : "Support Administration"
                                }}
                            </span>
                            <p class="contact-preview">
                                {{
                                    data.messages[data.messages.length - 1]
                                        ?.texte || "Nouvelle discussion..."
                                }}
                            </p>
                        </div>
                        <span
                            v-if="data.unread_count > 0"
                            class="unread-badge"
                            >{{ data.unread_count }}</span
                        >
                    </div>
                </div>
            </div>

            <div
                class="chat-window"
                :class="{ 'hidden-mobile': !selectedContactId }"
            >
                <template v-if="selectedContactId">
                    <div
                        class="chat-header border-bottom-1 surface-border flex align-items-center"
                    >
                        <Button
                            icon="pi pi-arrow-left"
                            class="md:hidden p-button-text"
                            @click="selectedContactId = null"
                        />
                        <Avatar
                            icon="pi pi-comments"
                            shape="circle"
                            class="bg-primary text-white mr-3"
                        />
                        <span class="font-bold"
                            >Discussion Concours #{{ form.concour_id }}</span
                        >
                    </div>

                    <div ref="chatScroll" class="chat-messages-container">
                        <div
                            v-for="msg in localConversations[selectedContactId]
                                .messages"
                            :key="msg.id"
                            :class="[
                                'message-row',
                                (
                                    isAdmin
                                        ? isFromAdmin(msg)
                                        : msg.emetteur_id === user.id
                                )
                                    ? 'row-me'
                                    : 'row-them',
                            ]"
                        >
                            <div class="message-bubble-wrapper">
                                <div
                                    :class="[
                                        'bubble',
                                        (
                                            isAdmin
                                                ? isFromAdmin(msg)
                                                : msg.emetteur_id === user.id
                                        )
                                            ? 'bubble-me'
                                            : 'bubble-them shadow-1',
                                    ]"
                                >
                                    <small
                                        v-if="isAdmin && isFromAdmin(msg)"
                                        class="block font-bold mb-1"
                                        style="font-size: 0.7rem; opacity: 0.8"
                                    >
                                        {{ msg.emetteur.name }} (Admin)
                                    </small>
                                    {{ msg.texte }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="chat-input-bar">
                        <div class="input-container surface-100">
                            <InputText
                                v-model="form.texte"
                                placeholder="Répondre..."
                                class="message-input"
                                @keyup.enter="submit"
                            />
                            <Button
                                icon="pi pi-send"
                                @click="submit"
                                :loading="form.processing"
                                class="p-button-text p-button-rounded"
                            />
                        </div>
                    </div>
                </template>
                <div v-else class="empty-state">
                    Sélectionnez une discussion pour répondre
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.messagerie-wrapper {
    display: flex;
    height: calc(100vh - 170px);
    padding: 0 !important;
    overflow: hidden;
}
.sidebar-contacts {
    flex: 0 0 320px;
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--surface-border);
}
.header-sidebar {
    padding: 1.2rem;
    border-bottom: 1px solid var(--surface-border);
    background: var(--surface-50);
}
.contacts-list {
    flex: 1;
    overflow-y: auto;
    background: #fff;
}
.contact-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    cursor: pointer;
    border-bottom: 1px solid var(--surface-ground);
    gap: 12px;
    position: relative;
}
.active-item {
    background: var(--blue-50) !important;
    border-left: 4px solid var(--primary-color);
}
.contact-name {
    font-weight: bold;
    color: var(--surface-900);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 180px;
    font-size: 0.9rem;
}
.contact-preview {
    margin: 0;
    font-size: 0.8rem;
    color: var(--surface-600);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.chat-window {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #fff;
}
.chat-header {
    padding: 1rem;
}
.chat-messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    background: #f8fafc;
}
.message-row {
    display: flex;
    margin-bottom: 1rem;
    width: 100%;
}
.row-me {
    justify-content: flex-end;
}
.bubble {
    padding: 0.8rem 1.2rem;
    border-radius: 1rem;
    font-size: 0.95rem;
    max-width: 75%;
    /* AJOUTS ICI */
    word-wrap: break-word; /* Coupe les mots longs pour éviter le débordement horizontal */
    overflow-wrap: break-word; /* Standard moderne pour le retour à la ligne forcé */
    white-space: pre-wrap; /* Préserve les sauts de ligne manuels tout en gérant le retour auto */
}
.bubble-me {
    background: var(--primary-color);
    color: #fff;
    border-bottom-right-radius: 0.2rem;
}
.bubble-them {
    background: #fff;
    color: var(--surface-900);
    border-bottom-left-radius: 0.2rem;
}

/* Restored Input Style */
.chat-input-bar {
    padding: 1rem;
    border-top: 1px solid var(--surface-border);
}
.input-container {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 15px;
    border-radius: 2.5rem;
}
.message-input {
    flex: 1;
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
}
.message-input:focus {
    outline: none !important;
    box-shadow: none !important;
}

.unread-badge {
    background-color: #ef4444;
    color: white;
    border-radius: 50%;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    position: absolute;
    right: 10px;
    top: 15px;
}
.empty-state {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fafafa;
    color: #999;
}
@media screen and (max-width: 768px) {
    .hidden-mobile {
        display: none !important;
    }
}
</style>
