import { useEffect } from 'react';
import { useElementsStore } from '../store/elements';
import { useUIStore } from '../store/ui';
import { useTemporalStore } from '../store/temporal';

export function useKeyboardShortcuts() {
  const {
    selectedIds, duplicateElement, deleteElement,
    groupElements, ungroupElement, lockElements,
    hideElements, copyStyles, pasteStyles, tree,
  } = useElementsStore();

  const {
    toggleLeftPanel, toggleRightPanel, toggleAIPanel,
    toggleRulers, toggleGuides, setDevice, setZoom, zoom,
  } = useUIStore();

  const { undo, redo } = useTemporalStore();

  useEffect(() => {
    const handler = (e: KeyboardEvent) => {
      // Don't fire when typing in an input/textarea
      const tag = (e.target as HTMLElement)?.tagName?.toLowerCase();
      if (['input','textarea','select'].includes(tag)) return;

      const ctrl  = e.ctrlKey || e.metaKey;
      const shift = e.shiftKey;
      const alt   = e.altKey;
      const key   = e.key.toLowerCase();

      // ── Undo / Redo ────────────────────────────────────────────
      if (ctrl && !shift && key === 'z') { e.preventDefault(); undo(); return; }
      if (ctrl && shift  && key === 'z') { e.preventDefault(); redo(); return; }
      if (ctrl && key === 'y')           { e.preventDefault(); redo(); return; }

      // ── Save ───────────────────────────────────────────────────
      if (ctrl && !shift && key === 's') {
        e.preventDefault();
        document.dispatchEvent(new CustomEvent('nexusbuilder:action:save'));
        return;
      }
      if (ctrl && shift && key === 's') {
        e.preventDefault();
        document.dispatchEvent(new CustomEvent('nexusbuilder:action:publish'));
        return;
      }

      // ── Selection ops (require selection) ─────────────────────
      if (selectedIds.length > 0) {
        // Duplicate
        if (ctrl && !shift && !alt && key === 'd') {
          e.preventDefault();
          selectedIds.forEach(id => duplicateElement(id));
          return;
        }

        // Delete
        if ((key === 'delete' || key === 'backspace') && !ctrl) {
          e.preventDefault();
          selectedIds.forEach(id => deleteElement(id));
          return;
        }

        // Group
        if (ctrl && !shift && !alt && key === 'g') {
          e.preventDefault();
          if (selectedIds.length > 1) groupElements(selectedIds);
          return;
        }

        // Ungroup
        if (ctrl && shift && !alt && key === 'g') {
          e.preventDefault();
          selectedIds.forEach(id => ungroupElement(id));
          return;
        }

        // Lock / unlock
        if (ctrl && !shift && !alt && key === 'l') {
          e.preventDefault();
          lockElements(selectedIds);
          return;
        }

        // Hide / show
        if (ctrl && !shift && !alt && key === 'h') {
          e.preventDefault();
          hideElements(selectedIds);
          return;
        }

        // Copy styles
        if (ctrl && alt && key === 'c') {
          e.preventDefault();
          copyStyles(selectedIds[0]);
          return;
        }

        // Paste styles
        if (ctrl && alt && key === 'v') {
          e.preventDefault();
          selectedIds.forEach(id => pasteStyles(id));
          return;
        }

        // Arrow key nudge
        const NUDGE = shift ? 10 : 1;
        if (['arrowup','arrowdown','arrowleft','arrowright'].includes(key)) {
          e.preventDefault();
          const axis  = key.includes('up') || key.includes('down') ? 'y' : 'x';
          const delta = (key === 'arrowup' || key === 'arrowleft') ? -NUDGE : NUDGE;
          selectedIds.forEach(id => {
            const el = useElementsStore.getState().getElement(id);
            if (!el) return;
            const current = Number(el.settings?.position?.[axis] ?? 0);
            useElementsStore.getState().updateElement(id, {
              position: { ...(el.settings?.position ?? {}), [axis]: current + delta }
            });
          });
          return;
        }
      }

      // ── Panel toggles ──────────────────────────────────────────
      if (ctrl && key === '\\')          { e.preventDefault(); toggleLeftPanel();  return; }
      if (ctrl && shift && key === '\\') { e.preventDefault(); toggleRightPanel(); return; }
      if (ctrl && shift && key === 'p')  { e.preventDefault(); toggleAIPanel();    return; }
      if (ctrl && key === 'r')           { e.preventDefault(); toggleRulers();     return; }
      if (ctrl && key === "'")           { e.preventDefault(); toggleGuides();     return; }

      // ── Device preview ─────────────────────────────────────────
      if (ctrl && shift && key === '1') { e.preventDefault(); setDevice('desktop'); return; }
      if (ctrl && shift && key === '2') { e.preventDefault(); setDevice('tablet');  return; }
      if (ctrl && shift && key === '3') { e.preventDefault(); setDevice('mobile');  return; }

      // ── Zoom ───────────────────────────────────────────────────
      if (ctrl && key === '0') { e.preventDefault(); setZoom(1); return; }
      if (ctrl && key === '=') { e.preventDefault(); setZoom(Math.min(zoom + 0.1, 3));  return; }
      if (ctrl && key === '-') { e.preventDefault(); setZoom(Math.max(zoom - 0.1, 0.2)); return; }

      // ── Escape ─────────────────────────────────────────────────
      if (key === 'escape') {
        useElementsStore.getState().clearSelection();
        useUIStore.getState().setInlineEditId(null);
        return;
      }
    };

    window.addEventListener('keydown', handler);
    return () => window.removeEventListener('keydown', handler);
  }, [selectedIds, zoom]);
}
