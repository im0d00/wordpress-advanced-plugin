import React, { useRef, useCallback, useState } from 'react';
import { DndContext, DragOverlay, useSensors, useSensor, PointerSensor, KeyboardSensor } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { ElementRenderer } from './ElementRenderer';
import { DropZone } from './DropZone';
import { SelectionBox } from './SelectionBox';
import { SmartGuides } from './SmartGuides';
import { Rulers } from './Rulers';
import { useElementsStore } from '../../store/elements';
import { useUIStore } from '../../store/ui';
import { useCanvasPan } from '../../hooks/useCanvasPan';
import { useCanvasZoom } from '../../hooks/useCanvasZoom';
import styles from './Canvas.module.css';

export const Canvas: React.FC = () => {
  const { tree, addElement, moveElement, clearSelection } = useElementsStore();
  const { zoom, device, showRulers, showGuides } = useUIStore();
  const canvasRef   = useRef<HTMLDivElement>(null);
  const viewportRef = useRef<HTMLDivElement>(null);

  const { isPanning, panOffset } = useCanvasPan(viewportRef);
  const { handleWheel } = useCanvasZoom();

  const [activeDragId, setActiveDragId] = useState<string | null>(null);

  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
    useSensor(KeyboardSensor)
  );

  const DEVICE_WIDTHS: Record<string, number> = {
    desktop: 1280,
    laptop:  1024,
    tablet:  768,
    mobile:  375,
  };

  const canvasWidth = DEVICE_WIDTHS[device] ?? 1280;

  const handleDragStart = useCallback(({ active }: any) => {
    setActiveDragId(active.id);
  }, []);

  const handleDragEnd = useCallback(({ active, over }: any) => {
    setActiveDragId(null);
    if (!over || active.id === over.id) return;

    // New element from left panel (has data.isNew flag)
    if (active.data.current?.isNew) {
      addElement(
        { type: active.data.current.elementType },
        over.data.current?.parentId ?? null,
        over.data.current?.insertIndex ?? 0
      );
      return;
    }

    // Move existing element
    moveElement(
      active.id,
      over.data.current?.parentId ?? null,
      over.data.current?.insertIndex ?? 0
    );
  }, [addElement, moveElement]);

  const handleCanvasClick = useCallback((e: React.MouseEvent) => {
    if (e.target === canvasRef.current || e.target === viewportRef.current) {
      clearSelection();
    }
  }, [clearSelection]);

  return (
    <div
      className={styles.canvasViewport}
      ref={viewportRef}
      onWheel={handleWheel}
      onClick={handleCanvasClick}
      style={{
        transform: `translate(${panOffset.x}px, ${panOffset.y}px) scale(${zoom})`,
        transformOrigin: '50% 0',
        cursor: isPanning ? 'grabbing' : 'default',
      }}
    >
      {showRulers && <Rulers zoom={zoom} offset={panOffset} />}

      <DndContext sensors={sensors} onDragStart={handleDragStart} onDragEnd={handleDragEnd}>
        <div
          ref={canvasRef}
          className={styles.canvas}
          style={{ width: canvasWidth, minHeight: 600 }}
          data-device={device}
        >
          {showGuides && <SmartGuides />}
          <SelectionBox canvasRef={canvasRef} />

          <SortableContext items={tree.map(n => n.id)} strategy={verticalListSortingStrategy}>
            {tree.map((node, idx) => (
              <React.Fragment key={node.id}>
                <DropZone parentId={null} insertIndex={idx} />
                <ElementRenderer node={node} depth={0} />
              </React.Fragment>
            ))}
            <DropZone parentId={null} insertIndex={tree.length} />
          </SortableContext>
        </div>

        <DragOverlay>
          {activeDragId && <DragOverlayPreview id={activeDragId} />}
        </DragOverlay>
      </DndContext>
    </div>
  );
};
