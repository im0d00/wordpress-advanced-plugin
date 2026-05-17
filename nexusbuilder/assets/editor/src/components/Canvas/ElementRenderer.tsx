import React, { memo, useCallback, useRef } from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { ResizeHandle } from './ResizeHandle';
import { ElementToolbar } from './ElementToolbar';
import { useElementsStore } from '../../store/elements';
import { useUIStore } from '../../store/ui';
import { elementRegistry } from '../../elements/registry';
import { NexusElement } from '../../types';
import styles from './ElementRenderer.module.css';

interface Props {
  node: NexusElement;
  depth: number;
}

export const ElementRenderer: React.FC<Props> = memo(({ node, depth }) => {
  const { selectedIds, hoveredId, selectElement, setHovered, updateElement } = useElementsStore();
  const { device } = useUIStore();

  const isSelected = selectedIds.includes(node.id);
  const isHovered  = hoveredId === node.id;

  const {
    attributes, listeners, setNodeRef,
    transform, transition, isDragging,
  } = useSortable({ id: node.id, data: { parentId: node.parentId } });

  const elementDef = elementRegistry.get(node.type);
  if (!elementDef) return <div className={styles.unknownElement}>Unknown: {node.type}</div>;

  const ElementComponent = elementDef.component;

  const handleClick = useCallback((e: React.MouseEvent) => {
    e.stopPropagation();
    selectElement(node.id, e.shiftKey);
  }, [node.id, selectElement]);

  const handleDoubleClick = useCallback((e: React.MouseEvent) => {
    e.stopPropagation();
    // Enter inline text edit mode
    if (elementDef.inlineEditable) {
      useUIStore.getState().setInlineEditId(node.id);
    }
  }, [node.id, elementDef.inlineEditable]);

  const containerStyle = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.4 : 1,
  };

  return (
    <div
      ref={setNodeRef}
      style={containerStyle}
      id={`nexus-el-${node.id}`}
      data-element-id={node.id}
      data-element-type={node.type}
      className={[
        styles.elementWrapper,
        isSelected ? styles.selected : '',
        isHovered  ? styles.hovered  : '',
        node.settings?.locked ? styles.locked : '',
        node.settings?.hidden ? styles.hidden : '',
      ].join(' ')}
      onClick={handleClick}
      onDoubleClick={handleDoubleClick}
      onMouseEnter={() => setHovered(node.id)}
      onMouseLeave={() => setHovered(null)}
    >
      {/* Drag handle — shown on hover/select */}
      {(isSelected || isHovered) && !node.settings?.locked && (
        <div className={styles.dragHandle} {...attributes} {...listeners} title="Drag to move">
          ⠿
        </div>
      )}

      {/* Element toolbar (duplicate, delete, move up/down) */}
      {isSelected && <ElementToolbar nodeId={node.id} />}

      {/* The actual element rendered by its React component */}
      <ElementComponent
        settings={node.settings}
        device={device}
        isSelected={isSelected}
        onSettingsChange={(patch) => updateElement(node.id, patch)}
        nodeId={node.id}
      >
        {/* Render children recursively */}
        {node.children?.map((child, idx) => (
          <ElementRenderer key={child.id} node={child} depth={depth + 1} />
        ))}
      </ElementComponent>

      {/* Resize handles — 8-point */}
      {isSelected && !node.settings?.locked && (
        <ResizeHandle nodeId={node.id} />
      )}
    </div>
  );
});
