<script setup>
import { ref, nextTick, onMounted, watch } from "vue";
import { useForm, usePage, Head, router } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Avatar from "primevue/avatar";
import Select from "primevue/select";

const props = defineProps({
    conversations: Object, // Format: { concours_ID: { messages: [], unread_count: X } }
    mesConcours: Array,
});

const user = usePage().props.auth.user;
const selectedKey = ref(null);
const chatScroll = ref(null);
const localConversations = ref({ ...props.conversations });

// Synchro avec les données du serveur
watch(
    () => props.conversations,
    (newVal) => {
        localConversations.value = { ...newVal };
        scrollToBottom();
    },
    { deep: true },
);

const form = useForm({
    concour_id: null,
    texte: "",
});

/**
 * Sélectionne une conversation et marque les messages comme lus
 */
const selectConversation = (key) => {
    selectedKey.value = key;
    const data = localConversations.value[key];

    if (data) {
        // Extraction de l'ID du concours depuis la clé (ex: "concours_5" -> 5)
        const concourId = key.split("_")[1];
        form.concour_id = parseInt(concourId);

        // Si il y a des messages non lus, on informe le serveur
        if (data.unread_count > 0) {
            // Mise à jour locale immédiate pour la réactivité (UI)
            data.unread_count = 0;

            router.patch(
                route("candidat.messagerie.read", concourId),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                },
            );
        }
    }
    scrollToBottom();
};

/**
 * Initialise une discussion pour un concours sélectionné dans la liste déroulante
 */
const startNewDiscussion = (concourId) => {
    if (!concourId) return;
    const key = "concours_" + concourId;

    if (!localConversations.value[key]) {
        localConversations.value[key] = { messages: [], unread_count: 0 };
    }

    selectConversation(key);
};

const scrollToBottom = async () => {
    await nextTick();
    if (chatScroll.value) {
        chatScroll.value.scrollTop = chatScroll.value.scrollHeight;
    }
};

const submit = () => {
    if (!form.texte.trim() || form.processing) return;

    form.post(route("candidat.messagerie.store"), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset("texte");
            scrollToBottom();
        },
    });
};

onMounted(() => {
    scrollToBottom();
});
</script>

<template>
    <AppLayout>
        <Head title="Ma Messagerie" />

        <div class="messagerie-wrapper card shadow-4">
            <div
                :class="[
                    'sidebar-contacts',
                    selectedKey ? 'hidden-mobile' : '',
                ]"
            >
                <div class="header-sidebar">
                    <h5 class="m-0 font-bold text-primary uppercase mb-3">
                        Messages
                    </h5>
                    <Select
                 <Select
    v-model="form.concour_id"
    :options="mesConcours"
    optionLabel="intitule"
    optionValue="id"
    placeholder="Nouveau message sur..."
    @change="(e) => startNewDiscussion(e.value)"
    class="w-full border-round-pill"
    filter
>
    <template #empty>
        <div class="flex align-items-center justify-content-center p-3 text-surface-500">
            <i class="pi pi-inbox mr-2"></i>
            <span>Pas encore de conversation</span>
        </div>
    </template>
</Select>
                </div>

                <div class="contacts-list">
                    <div
                        v-for="(data, key) in localConversations"
                        :key="key"
                        @click="selectConversation(key)"
                        :class="[
                            'contact-item',
                            selectedKey === key ? 'active-item' : '',
                        ]"
                    >
                        <div class="relative">
                            <Avatar
                                icon="pi pi-envelope"
                                size="large"
                                shape="circle"
                                class="bg-blue-100 text-blue-700 font-bold"
                            />
                            <span
                                v-if="data.unread_count > 0"
                                class="unread-badge"
                            >
                                {{ data.unread_count }}
                            </span>
                        </div>

                        <div class="contact-info">
                            <div class="contact-name-row">
                                <span
                                    class="contact-name"
                                    :class="{
                                        'font-black': data.unread_count > 0,
                                    }"
                                >
                                    {{
                                        data.messages?.[0]?.concour?.intitule ||
                                        "Discussion Concours"
                                    }}
                                </span>
                                <small
                                    v-if="data.messages?.length > 0"
                                    class="text-500"
                                >
                                    {{
                                        new Date(
                                            data.messages[
                                                data.messages.length - 1
                                            ].created_at,
                                        ).toLocaleDateString()
                                    }}
                                </small>
                            </div>
                            <p
                                class="contact-preview"
                                :class="{
                                    'text-bold-unread': data.unread_count > 0,
                                }"
                            >
                                {{
                                    data.messages?.length > 0
                                        ? data.messages[
                                              data.messages.length - 1
                                          ].texte
                                        : "Démarrer la discussion..."
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div :class="['chat-window', !selectedKey ? 'hidden-mobile' : '']">
                <template v-if="selectedKey">
                    <div
                        class="chat-header bg-white border-bottom-1 surface-border"
                    >
                        <Button
                            icon="pi pi-arrow-left"
                            class="md:hidden p-button-text p-button-rounded mr-2"
                            @click="selectedKey = null"
                        />
                        <Avatar
                            icon="pi pi-comments"
                            shape="circle"
                            class="bg-primary text-white mr-3 shadow-1"
                        />
                        <div>
                            <span
                                class="font-bold block line-height-2 text-900"
                            >
                                {{
                                    localConversations[selectedKey]
                                        ?.messages?.[0]?.concour?.intitule ||
                                    "Nouvelle discussion"
                                }}
                            </span>
                            <small class="text-green-500 font-medium"
                                >Administration</small
                            >
                        </div>
                    </div>

                    <div
                        ref="chatScroll"
                        class="chat-messages-container bg-surface-50"
                    >
                        <div
                            v-if="
                                localConversations[selectedKey]?.messages
                                    ?.length === 0
                            "
                            class="flex justify-content-center p-5"
                        >
                            <div
                                class="text-center surface-card p-4 border-round shadow-1"
                            >
                                <i
                                    class="pi pi-info-circle text-primary text-2xl mb-2"
                                ></i>
                                <p class="m-0 text-600">
                                    Posez votre question concernant ce concours.
                                </p>
                            </div>
                        </div>

                        <div
                            v-for="msg in localConversations[selectedKey]
                                ?.messages"
                            :key="msg.id"
                            :class="[
                                'message-row',
                                msg.emetteur_id === user.id
                                    ? 'row-me'
                                    : 'row-them',
                            ]"
                        >
                            <div class="message-bubble-wrapper">
                                <div
                                    :class="[
                                        'bubble',
                                        msg.emetteur_id === user.id
                                            ? 'bubble-me'
                                            : 'bubble-them shadow-1',
                                    ]"
                                >
                                    {{ msg.texte }}
                                </div>
                                <small class="message-time">
                                    {{
                                        new Date(
                                            msg.created_at,
                                        ).toLocaleTimeString([], {
                                            hour: "2-digit",
                                            minute: "2-digit",
                                        })
                                    }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="chat-input-bar bg-white">
                        <div class="input-container surface-100 shadow-2">
                            <InputText
                                v-model="form.texte"
                                placeholder="Écrivez votre message..."
                                class="message-input"
                                @keyup.enter="submit"
                            />
                            <Button
                                icon="pi pi-send"
                                class="p-button-rounded"
                                @click="submit"
                                :loading="form.processing"
                                :disabled="!form.texte.trim()"
                            />
                        </div>
                    </div>
                </template>
                <div v-else class="empty-state">
                    <i class="pi pi-envelope text-6xl text-200 mb-3"></i>
                    <p class="text-500">
                        Sélectionnez un concours pour voir vos échanges
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.relative {
    position: relative;
}
.unread-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background-color: #ef4444;
    color: white;
    border-radius: 50%;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    font-weight: bold;
    border: 2px solid white;
    z-index: 5;
}
.font-black {
    font-weight: 800 !important;
    color: var(--surface-900) !important;
}
.text-bold-unread {
    font-weight: 600 !important;
    color: var(--surface-800) !important;
}
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
}
.active-item {
    background: var(--blue-50) !important;
    border-left: 4px solid var(--primary-color);
}
.contact-info {
    flex: 1;
    overflow: hidden;
}
.contact-name-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.contact-name {
    font-weight: bold;
    color: var(--surface-900);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 180px;
}
.contact-preview {
    margin: 0;
    font-size: 0.85rem;
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
    position: relative;
}
.chat-header {
    padding: 1rem;
    display: flex;
    align-items: center;
    z-index: 10;
}
.chat-messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
}
.message-row {
    display: flex;
    margin-bottom: 1rem;
    width: 100%;
}
.row-me {
    justify-content: flex-end;
}
.row-them {
    justify-content: flex-start;
}
.message-bubble-wrapper {
    max-width: 75%;
}
.bubble {
    padding: 0.8rem 1.2rem;
    border-radius: 1rem;
    font-size: 0.95rem;
    line-height: 1.4;
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
.message-time {
    display: block;
    margin-top: 4px;
    font-size: 0.7rem;
    color: var(--surface-500);
    text-align: inherit;
}
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
.empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #fafafa;
}
@media screen and (max-width: 768px) {
    .hidden-mobile {
        display: none !important;
    }
    .sidebar-contacts {
        flex: 1;
    }
}
</style>
