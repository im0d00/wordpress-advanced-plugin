import { useEffect, useRef, useCallback } from 'react';
import { useElementsStore } from '../store/elements';
import { useUIStore } from '../store/ui';

interface AutoSaveOptions {
  postId:   number;
  interval: number; // ms
}

export function useAutoSave({ postId, interval }: AutoSaveOptions) {
  const timerRef      = useRef<ReturnType<typeof setInterval> | null>(null);
  const isDirtyRef    = useRef(false);
  const isSavingRef   = useRef(false);

  const { tree }      = useElementsStore();
  const { setStatus, nonce, restUrl } = useUIStore();

  // Track dirty state
  useEffect(() => {
    isDirtyRef.current = true;
  }, [tree]);

  const save = useCallback(async () => {
    if (!isDirtyRef.current || isSavingRef.current) return;

    isSavingRef.current = true;
    setStatus('saving');

    try {
      const res = await fetch(`${restUrl}/nexusbuilder/v1/pages/${postId}`, {
        method:  'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-NB-Nonce':   nonce,
          'X-WP-Nonce':   nonce,
        },
        body: JSON.stringify({
          element_tree: useElementsStore.getState().tree,
          label:        'auto-save',
        }),
      });

      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      isDirtyRef.current  = false;
      setStatus('saved');

      // Show "Saved" for 2s then clear
      setTimeout(() => setStatus('idle'), 2000);

    } catch (err) {
      setStatus('error');
      console.error('NexusBuilder auto-save failed:', err);
    } finally {
      isSavingRef.current = false;
    }
  }, [postId, nonce, restUrl, setStatus]);

  // Set up interval
  useEffect(() => {
    timerRef.current = setInterval(save, interval);
    return () => { if (timerRef.current) clearInterval(timerRef.current); };
  }, [save, interval]);

  // Also save on window beforeunload if dirty
  useEffect(() => {
    const handler = (e: BeforeUnloadEvent) => {
      if (isDirtyRef.current) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
      }
    };
    window.addEventListener('beforeunload', handler);
    return () => window.removeEventListener('beforeunload', handler);
  }, []);

  return { save, isDirty: isDirtyRef.current };
}
