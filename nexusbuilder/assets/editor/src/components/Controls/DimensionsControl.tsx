import React, { useState } from 'react';

interface DimValue { top: string; right: string; bottom: string; left: string; }
interface Props { value: DimValue; onChange: (v: DimValue) => void; label: string; unit?: string; }

export const DimensionsControl: React.FC<Props> = ({ value = {top:'',right:'',bottom:'',left:''}, onChange, label, unit = 'px' }) => {
  const [linked, setLinked] = useState(true);

  const handleChange = (side: keyof DimValue, raw: string) => {
    const v = raw + unit;
    if (linked) {
      onChange({ top: v, right: v, bottom: v, left: v });
    } else {
      onChange({ ...value, [side]: v });
    }
  };

  const numVal = (s: string) => parseInt(s) || 0;

  return (
    <div className="control-row dimensions-control">
      <label className="control-label">{label}</label>
      <div className="dimensions-grid">
        <input className="dim-top"    type="number" value={numVal(value.top)}    onChange={e => handleChange('top',    e.target.value)} placeholder="T" />
        <input className="dim-right"  type="number" value={numVal(value.right)}  onChange={e => handleChange('right',  e.target.value)} placeholder="R" />
        <input className="dim-bottom" type="number" value={numVal(value.bottom)} onChange={e => handleChange('bottom', e.target.value)} placeholder="B" />
        <input className="dim-left"   type="number" value={numVal(value.left)}   onChange={e => handleChange('left',   e.target.value)} placeholder="L" />
        <button
          className={`dim-link ${linked ? 'linked' : ''}`}
          onClick={() => setLinked(l => !l)}
          title={linked ? 'Unlink sides' : 'Link all sides'}
        >
          {linked ? '🔗' : '⛓️'}
        </button>
      </div>
      <select className="dim-unit" defaultValue={unit}>
        {['px','%','rem','em','vw','vh'].map(u => <option key={u}>{u}</option>)}
      </select>
    </div>
  );
};
