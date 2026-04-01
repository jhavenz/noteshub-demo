document.addEventListener('alpine:init', () => {
  Alpine.directive('sse', (el, { expression }, { cleanup }) => {
    const comp = Alpine.$data(el);
    let es, retryTimer, retryDelay = 1000;

    function connect() {
      if (es) es.close();
      clearTimeout(retryTimer);
      comp.sseStatus = 'reconnecting';

      es = new EventSource(expression);

      es.onopen = () => {
        comp.sseStatus = 'connected';
        retryDelay = 1000;
      };

      es.onerror = () => {
        es.close();
        es = null;
        comp.sseStatus = 'reconnecting';
        retryTimer = setTimeout(() => {
          retryDelay = Math.min(retryDelay * 2, 30000);
          connect();
        }, retryDelay);
      };

      ['note.created', 'note.updated', 'note.deleted'].forEach(event => {
        es.addEventListener(event, (e) => {
          const data = JSON.parse(e.data);
          el.dispatchEvent(new CustomEvent('sse:' + event, { detail: data, bubbles: true }));
        });
      });
    }

    connect();
    cleanup(() => { if (es) es.close(); clearTimeout(retryTimer); });
  });
});
