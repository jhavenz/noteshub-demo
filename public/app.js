document.addEventListener('alpine:init', () => {
  Alpine.data('notesApp', () => ({
    notes: [],
    related: {},
    editingId: null,
    editBusy: false,
    relatedCache: {},
    composeBusy: false,
    sseStatus: 'disconnected',

    async init() {
      this.notes = await this.api('/notes');

      this.$el.addEventListener('sse:note.created', (e) => {
        const note = e.detail;
        this.notes = [note, ...this.notes.filter(n => String(n.id) !== String(note.id))];
        this.fetchAllRelated();
        this.$nextTick(() => this.flash(note.id, 'flash-add'));
      });

      this.$el.addEventListener('sse:note.updated', (e) => {
        const updated = e.detail;
        this.notes = this.notes.map(n => String(n.id) === String(updated.id) ? updated : n);
        this.fetchAllRelated();
        this.$nextTick(() => this.flash(updated.id, 'flash-update'));
      });

      this.$el.addEventListener('sse:note.deleted', (e) => {
        const deletedId = String(e.detail.id);
        this.notes = this.notes.filter(n => String(n.id) !== deletedId);
        this.fetchAllRelated();
      });

      this.fetchAllRelated();
    },

    async api(url, opts) {
      const r = await fetch(url, opts);
      return r.json();
    },

    async createNote() {
      const body = this.$refs.composeBody.value.trim();
      const title = this.$refs.composeTitle.value.trim();
      
      if (!title || !body) return;
      this.composeBusy = true;
      
      try {
        await this.api('/notes', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ title, body }),
        });
        this.$refs.composeBody.value = '';
        this.$refs.composeTitle.value = '';
      } finally {
        this.composeBusy = false;
      }
    },

    async saveEdit(id) {
      const body = this.$refs.editBody.value.trim();
      const title = this.$refs.editTitle.value.trim();
      if (!title) return;
      
      this.editBusy = true;
      
      try {
        await this.api(`/notes/${id}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ title, body }),
        });
        
        this.editingId = null;
      } finally {
        this.editBusy = false;
      }
    },

    async removeNote(id) {
      await fetch(`/notes/${id}`, { method: 'DELETE' });
    },

    async fetchAllRelated() {
      for (const note of this.notes) {
        if (this.editingId === note.id) continue;
        const data = await this.api(`/notes/${note.id}/related`);
        const newIds = data.map(r => r.id).join(',');
        const prev = this.relatedCache[note.id];
        if (prev !== undefined && prev !== newIds) {
          this.$nextTick(() => this.flash(note.id, 'flash-related'));
        }
        this.relatedCache[note.id] = newIds;
        this.related[note.id] = data;
      }
    },

    flash(id, cls) {
      const el = document.getElementById(`note-${id}`);
      if (!el) return;
      el.classList.add(cls);
      setTimeout(() => el.classList.remove(cls), 700);
    },

    formatTs(ts) {
      if (!ts) return '';
      return new Date(ts.replace(' ', 'T') + 'Z').toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    },
  }));
});
