BX.ready(() => {
    BX.message(arMessages);
    
    BX.Vue3.BitrixVue.createApp({
        data() {
            return {
                notes: [],
                newTitle: '',
                newText: '',
                editId: null,
                editTitle: '',
                editText: '',
                loading: false,
                error: null,
                apiToken: arParams.API_AUTH_TOKEN ?? '1234567890abcdef',
                apiBase: arParams.API_PATH ?? '/api/notes'
            };
        },
        mounted() {
            this.fetchNotes();
        },
        methods: {
            async fetchNotes() {
                this.loading = true;
                this.error = null;
                try {
                    const res = await fetch(this.apiBase, {
                        headers: { 'X-API-KEY': this.apiToken }
                    });
                    if (!res.ok) throw new Error(BX.message('RL_NOTES_ERROR_LOAD'));
                    this.notes = await res.json();
                } catch (e) {
                    this.error = e.message;
                } finally {
                    this.loading = false;
                }
            },
            async createNote() {
                console.log('createNote');
                if (!this.newTitle || !this.newText) return;
                try {
                    const res = await fetch(`${this.apiBase}/`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-API-KEY': this.apiToken
                        },
                        body: JSON.stringify({ title: this.newTitle, text: this.newText })
                    });
                    if (!res.ok) throw new Error(BX.message('RL_NOTES_ERROR_CREATE'));
                    const data = await res.json();
                    console.log(data);
                    this.notes.unshift({ id: data.id, title: this.newTitle, text: this.newText });
                    this.newTitle = '';
                    this.newText = '';
                } catch (e) {
                    this.error = e.message;
                }
            },
            startEdit(note) {
                this.editId = note.id;
                this.editTitle = note.title;
                this.editText = note.text;
            },
            cancelEdit() {
                this.editId = null;
                this.editTitle = '';
                this.editText = '';
            },
            async saveEdit(id) {
                if (!this.editTitle || !this.editText) return;
                try {
                    const res = await fetch(`${this.apiBase}/${id}/`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-API-KEY': this.apiToken
                        },
                        body: JSON.stringify({ title: this.editTitle, text: this.editText })
                    });
                    if (!res.ok) throw new Error(BX.message('RL_NOTES_ERROR_UPDATE'));
                    const note = this.notes.find(n => n.id === id);
                    note.title = this.editTitle;
                    note.text = this.editText;
                    this.cancelEdit();
                } catch (e) {
                    this.error = e.message;
                }
            },
            async deleteNote(id) {
                if (!confirm(BX.message('RL_NOTES_CONFIRM_DELETE'))) return;
                try {
                    const res = await fetch(`${this.apiBase}/${id}/`, {
                        method: 'DELETE',
                        headers: { 'X-API-KEY': this.apiToken }
                    });
                    if (!res.ok) throw new Error(BX.message('RL_NOTES_ERROR_DELETE'));
                    this.notes = this.notes.filter(n => n.id !== id);
                    if (this.editId === id) this.cancelEdit();
                } catch (e) {
                    this.error = e.message;
                }
            }
        }
    }).mount('#notes-app');
});
