import React, { useState } from 'react';
import { useElementsStore } from '../../store/elements';
import { elementRegistry } from '../../elements/registry';
import { ControlRenderer } from '../Controls/ControlRenderer';
import { ResponsiveToggle } from '../Controls/ResponsiveToggle';
import { StateToggle } from '../Controls/StateToggle';
import styles from './RightPanel.module.css';

type PanelTab = 'content' | 'style' | 'advanced' | 'motion';

export const RightPanel: React.FC = () => {
  const { selectedIds, getElement, updateElement } = useElementsStore();
  const [activeTab, setActiveTab] = useState<PanelTab>('content');
  const [activeDevice, setActiveDevice] = useState<'desktop'|'tablet'|'mobile'>('desktop');
  const [activeState, setActiveState] = useState<'normal'|'hover'|'focus'>('normal');

  const selectedId  = selectedIds[0];
  const node        = selectedId ? getElement(selectedId) : null;
  const elementDef  = node ? elementRegistry.get(node.type) : null;

  if (!node || !elementDef) {
    return (
      <aside className={styles.panel}>
        <div className={styles.emptyState}>
          <span>Click any element to edit it</span>
        </div>
      </aside>
    );
  }

  const schema       = elementDef.controls;
  const tabGroups    = schema.groups.filter((g: any) => g.tab === activeTab);
  const settings     = node.settings ?? {};

  const handleChange = (controlId: string, value: unknown) => {
    // Build the path depending on responsive and state
    if (schema.controls[controlId]?.responsive) {
      updateElement(selectedId, {
        [controlId]: {
          ...(settings[controlId] ?? {}),
          [activeDevice]: value,
        }
      });
    } else if (schema.controls[controlId]?.states) {
      updateElement(selectedId, {
        [`${controlId}_${activeState}`]: value,
      });
    } else {
      updateElement(selectedId, { [controlId]: value });
    }
  };

  const TABS: { id: PanelTab; label: string }[] = [
    { id: 'content',  label: 'Content'  },
    { id: 'style',    label: 'Style'    },
    { id: 'advanced', label: 'Advanced' },
    { id: 'motion',   label: 'Motion'   },
  ];

  return (
    <aside className={styles.panel}>
      {/* Element type header */}
      <div className={styles.panelHeader}>
        <i className={`ti ${elementDef.icon}`} />
        <span className={styles.elementLabel}>{elementDef.label}</span>
        <code className={styles.elementId}>#{node.id.slice(-6)}</code>
      </div>

      {/* Tab bar */}
      <div className={styles.tabBar}>
        {TABS.map(tab => (
          <button
            key={tab.id}
            className={activeTab === tab.id ? styles.activeTab : styles.tab}
            onClick={() => setActiveTab(tab.id)}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* Responsive toggle (shown on Style tab) */}
      {activeTab === 'style' && (
        <ResponsiveToggle active={activeDevice} onChange={setActiveDevice} />
      )}

      {/* State toggle (shown when controls have states) */}
      {activeTab === 'style' && elementDef.hasStates && (
        <StateToggle active={activeState} onChange={setActiveState} />
      )}

      {/* Controls */}
      <div className={styles.controlsArea}>
        {tabGroups.map((group: any) => (
          <div key={group.id} className={styles.controlGroup}>
            <div className={styles.groupLabel}>{group.label}</div>
            {group.controls.map((control: any) => (
              <ControlRenderer
                key={control.id}
                control={control}
                value={
                  control.responsive
                    ? (settings[control.id]?.[activeDevice] ?? settings[control.id]?.desktop ?? control.default)
                    : control.states
                      ? (settings[`${control.id}_${activeState}`] ?? control.default)
                      : (settings[control.id] ?? control.default)
                }
                onChange={(val) => handleChange(control.id, val)}
                device={activeDevice}
                state={activeState}
              />
            ))}
          </div>
        ))}
      </div>
    </aside>
  );
};
