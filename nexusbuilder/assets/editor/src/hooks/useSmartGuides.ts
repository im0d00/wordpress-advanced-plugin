import { useMemo } from 'react';
import { useElementsStore } from '../store/elements';
import { NexusElement } from '../types';

interface Rect { x: number; y: number; w: number; h: number; }
interface Guide { orientation: 'h' | 'v'; position: number; label?: string; }

function getRect(id: string): Rect | null {
  const el = document.querySelector(`[data-element-id="${id}"]`) as HTMLElement | null;
  if (!el) return null;
  const r = el.getBoundingClientRect();
  return { x: r.left, y: r.top, w: r.width, h: r.height };
}

export function useSmartGuides(draggingId: string | null) {
  const { tree, selectedIds } = useElementsStore();

  const guides = useMemo((): Guide[] => {
    if (!draggingId) return [];

    const dragRect = getRect(draggingId);
    if (!dragRect) return [];

    const THRESHOLD = 6; // snap threshold in px
    const result: Guide[] = [];

    // Collect all non-selected element rects
    const others = getAllIds(tree)
      .filter(id => id !== draggingId && !selectedIds.includes(id))
      .map(id => ({ id, rect: getRect(id) }))
      .filter(o => o.rect !== null) as { id: string; rect: Rect }[];

    for (const { rect } of others) {
      const dragMidX  = dragRect.x + dragRect.w / 2;
      const dragMidY  = dragRect.y + dragRect.h / 2;
      const otherMidX = rect.x + rect.w / 2;
      const otherMidY = rect.y + rect.h / 2;

      // Left edge align
      if (Math.abs(dragRect.x - rect.x) < THRESHOLD)
        result.push({ orientation: 'v', position: rect.x, label: `${Math.round(rect.x)}` });

      // Right edge align
      if (Math.abs(dragRect.x + dragRect.w - (rect.x + rect.w)) < THRESHOLD)
        result.push({ orientation: 'v', position: rect.x + rect.w });

      // Center-X align
      if (Math.abs(dragMidX - otherMidX) < THRESHOLD)
        result.push({ orientation: 'v', position: otherMidX });

      // Top edge align
      if (Math.abs(dragRect.y - rect.y) < THRESHOLD)
        result.push({ orientation: 'h', position: rect.y });

      // Bottom edge align
      if (Math.abs(dragRect.y + dragRect.h - (rect.y + rect.h)) < THRESHOLD)
        result.push({ orientation: 'h', position: rect.y + rect.h });

      // Center-Y align
      if (Math.abs(dragMidY - otherMidY) < THRESHOLD)
        result.push({ orientation: 'h', position: otherMidY });
    }

    return result;
  }, [draggingId, tree, selectedIds]);

  return guides;
}

function getAllIds(nodes: NexusElement[]): string[] {
  return nodes.flatMap(n => [n.id, ...getAllIds(n.children ?? [])]);
}
