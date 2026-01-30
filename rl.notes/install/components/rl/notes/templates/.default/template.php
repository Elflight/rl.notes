<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
\Bitrix\Main\UI\Extension::load("ui.vue3");
?>
<div id="notes-app">
    <h1><?= GetMessage('RL_NOTES_TITLE') ?></h1>

    <form @submit.prevent="createNote" id="new_note_form">
        <input v-model="newTitle" placeholder="<?= GetMessage('RL_NOTES_PLACEHOLDER_TITLE') ?>" required />
        <textarea v-model="newText" placeholder="<?= GetMessage('RL_NOTES_PLACEHOLDER_TEXT') ?>" required></textarea>
        <button type="submit"><?= GetMessage('RL_NOTES_ADD_BUTTON') ?></button>
    </form>

    <div v-if="loading"><?= GetMessage('RL_NOTES_LOADING') ?></div>
    <div v-if="error" style="color: red;">{{ error }}</div>

    <div id="notes_list">
        <div class="note_item" v-for="note in notes" :key="note.id">
            <div class="note_card" v-if="editId !== note.id">
                <div class="note_content">
                    <h2 class="note_title">{{ note.title }}</h2>
                    <div class="note_text">{{ note.text }}</div>
                </div>
                <div class="note_actions">
                    <button @click="startEdit(note)" title="<?= GetMessage('RL_NOTES_EDIT') ?>">
                        <svg viewBox="0 0 24 24">
                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1.004 1.004 0 0 0 0-1.42l-2.34-2.34a1.004 1.004 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.82z"/>
                        </svg>
                    </button>
                    <button @click="deleteNote(note.id)" title="<?= GetMessage('RL_NOTES_DELETE') ?>">
                        <svg viewBox="0 0 24 24">
                            <path d="M16 9v10H8V9h8m-1.5-6h-5l-1 1H5v2h14V4h-4.5l-1-1z"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div v-else class="note_edit">
                <input v-model="editTitle" placeholder="<?= GetMessage('RL_NOTES_PLACEHOLDER_TITLE') ?>" />
                <textarea v-model="editText" placeholder="<?= GetMessage('RL_NOTES_PLACEHOLDER_TEXT') ?>"><?= GetMessage('RL_NOTES_PLACEHOLDER_TEXT') ?></textarea>
                <button @click="saveEdit(note.id)" class="note_save"><?= GetMessage('RL_NOTES_SAVE') ?></button>
                <button @click="cancelEdit"><?= GetMessage('RL_NOTES_CANCEL') ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    const arParams = <?= \Bitrix\Main\Web\Json::encode($arParams) ?>;
    const arMessages = <?= \Bitrix\Main\Web\Json::encode(\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__)) ?>;
</script>
