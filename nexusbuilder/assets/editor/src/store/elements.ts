// store/elements.ts — The core element tree store
import { create } from 'zustand';
import { temporal } from 'zundo'; // Undo/redo middleware
import { NexusElement, ElementSettings } from '../types';

interface ElementsState {
  tree: NexusElement[];
  selectedIds: string[];
  hoveredId: string | null;
  
  // Actions
  addElement: (element: NexusElement, parentId: string | null, index?: number) => void;
  updateElement: (id: string, settings: Partial<ElementSettings>) => void;
  moveElement: (id: string, newParentId: string | null, newIndex: number) => void;
  duplicateElement: (id: string) => void;
  deleteElement: (id: string) => void;
  groupElements: (ids: string[]) => void;
  ungroupElement: (id: string) => void;
  
  // Selection
  selectElement: (id: string, addToSelection?: boolean) => void;
  selectAll: (parentId?: string) => void;
  clearSelection: () => void;
  
  // Clipboard
  copyStyles: (id: string) => void;
  pasteStyles: (targetId: string) => void;
  
  // Bulk operations
  lockElements: (ids: string[]) => void;
  hideElements: (ids: string[]) => void;
}

export const useElementsStore = create<ElementsState>()(
  temporal(
    (set, get) => ({
      tree: [],
      selectedIds: [],
      hoveredId: null,
      _clipboard: null as ElementSettings | null,

      addElement: (element, parentId, index = -1) => set(state => ({
        tree: insertIntoTree(state.tree, element, parentId, index)
      })),

      updateElement: (id, settings) => set(state => ({
        tree: updateInTree(state.tree, id, settings)
      })),

      moveElement: (id, newParentId, newIndex) => set(state => ({
        tree: moveInTree(state.tree, id, newParentId, newIndex)
      })),

      duplicateElement: (id) => set(state => {
        const el = findInTree(state.tree, id);
        if (!el) return state;
        const clone = deepCloneWithNewIds(el);
        const parentId = findParentId(state.tree, id);
        const index    = findIndexInParent(state.tree, id) + 1;
        return { tree: insertIntoTree(state.tree, clone, parentId, index) };
      }),

      deleteElement: (id) => set(state => ({
        tree: removeFromTree(state.tree, id),
        selectedIds: state.selectedIds.filter(sid => sid !== id),
      })),

      selectElement: (id, addToSelection = false) => set(state => ({
        selectedIds: addToSelection
          ? state.selectedIds.includes(id)
            ? state.selectedIds.filter(sid => sid !== id)
            : [...state.selectedIds, id]
          : [id]
      })),

      clearSelection: () => set({ selectedIds: [] }),

      copyStyles: (id) => {
        const el = findInTree(get().tree, id);
        if (el) set({ _clipboard: el.settings } as any);
      },

      pasteStyles: (targetId) => {
        const clipboard = (get() as any)._clipboard;
        if (clipboard) get().updateElement(targetId, clipboard);
      },
      
      // ... other actions
    }),
    {
      limit: 200, // 200-step undo history
      handleSet: (handleSet) => (fn) => handleSet(fn),
    }
  )
);
