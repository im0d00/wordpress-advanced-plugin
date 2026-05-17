import React, { useState } from 'react';
import { DndContext, closestCenter } from '@dnd-kit/core';
import { SortableContext, useSortable, verticalListSortingStrategy, arrayMove } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { ControlRenderer } from './ControlRenderer';
import { nanoid } from 'nanoid';

interface RepeaterItem { _id: string; [key: string]: any; }
interface Props {
  value: RepeaterItem[];
  onChange: (v: RepeaterItem[]) => void;
  label: string;
  itemControls: any[];
  defaultItem?: Record<string, any>;
  maxItems?: number;
}

export const RepeaterControl: React.FC<Props> = ({
  value = [], onChange, label, itemControls, defaultItem = {}, maxItems
}) => {
  const [expandedId, setExpandedId] = useState<string | null>(null);

  const addItem = () => {
    if (maxItems && value.length >= maxItems) return;
    const newItem = { _id: nanoid(8), ...defaultItem };
    onChange([...value, newItem]);
    setExpandedId(newItem._id);
  };

  const removeItem = (id: string) => onChange(value.filter(i => i._id !== id));

  const updateItem = (id: string, patch: Record<string, any>) =>
    onChange(value.map(i => i._id === id ? { ...i, ...patch } : i));

  const handleDragEnd = ({ active, over }: any) => {
    if (!over || active.id === over.id) return;
    const from = value.findIndex(i => i._id === active.id);
    const to   = value.findIndex(i => i._id === over.id);
    onChange(arrayMove(value, from, to));
  };

  return (
    <div className="control-row repeater-control">
      <div className="repeater-header">
        <label className="control-label">{label}</label>
        <button className="repeater-add" onClick={addItem}
          disabled={!!(maxItems && value.length >= maxItems)}>
          + Add item
        </button>
      </div>

      <DndContext collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
        <SortableContext items={value.map(i => i._id)} strategy={verticalListSortingStrategy}>
          {value.map((item, idx) => (
            <SortableRepeaterItem
              key={item._id}
              item={item}
              index={idx}
              controls={itemControls}
              expanded={expandedId === item._id}
              onToggle={() => setExpandedId(id => id === item._id ? null : item._id)}
              onRemove={() => removeItem(item._id)}
              onUpdate={patch => updateItem(item._id, patch)}
            />
          ))}
        </SortableContext>
      </DndContext>
    </div>
  );
};

const SortableRepeaterItem: React.FC<any> = ({ item, index, controls, expanded, onToggle, onRemove, onUpdate }) => {
  const { attributes, listeners, setNodeRef, transform, transition } = useSortable({ id: item._id });

  return (
    <div ref={setNodeRef} style={{ transform: CSS.Transform.toString(transform), transition }}
      className="repeater-item">
      <div className="repeater-item-header">
        <span className="repeater-drag" {...attributes} {...listeners}>⠿</span>
        <button className="repeater-toggle" onClick={onToggle}>
          {item.title || item.label || `Item ${index + 1}`} {expanded ? '▲' : '▼'}
        </button>
        <button className="repeater-remove" onClick={onRemove} title="Remove">×</button>
      </div>
      {expanded && (
        <div className="repeater-item-body">
          {controls.map((ctrl: any) => (
            <ControlRenderer
              key={ctrl.id}
              control={ctrl}
              value={item[ctrl.id] ?? ctrl.default}
              onChange={val => onUpdate({ [ctrl.id]: val })}
            />
          ))}
        </div>
      )}
    </div>
  );
};
