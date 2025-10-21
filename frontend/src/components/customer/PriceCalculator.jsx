import React, { useEffect, useMemo, useState } from 'react';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

export default function PriceCalculator({ artwork, onChange }) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [rules, setRules] = useState({ options: {} });
  const [selected, setSelected] = useState({});

  useEffect(() => {
    if (!artwork?.id) return;
    let cancelled = false;
    async function load() {
      setLoading(true); setError('');
      try {
        const res = await fetch(`${API_BASE}/customer/pricing-rules.php?artwork_id=${encodeURIComponent(artwork.id)}`);
        const data = await res.json();
        if (!cancelled) {
          if (data.status === 'success') {
            // Use backend-provided options only (per-artwork specificity)
            // deliveryDate is handled as special field, not part of options math (except rush fee)
            setRules({ base_price: data.base_price, options: data.options || {} });
          } else {
            setError(data.message || 'Failed to load pricing rules');
          }
        }
      } catch (e) {
        if (!cancelled) setError('Network error loading pricing rules');
      } finally { if (!cancelled) setLoading(false); }
    }
    load();
    return () => { cancelled = true; };
  }, [artwork?.id]);

  const total = useMemo(() => {
    const base = Number(rules.base_price ?? artwork?.price ?? 0);
    let subtotal = base;
    const opts = rules.options || {};
    for (const key of Object.keys(opts)) {
      const spec = opts[key];
      if (!spec) continue;
      if (spec.type === 'select') {
        const val = selected[key];
        const found = (spec.values || []).find(v => String(v.value) === String(val));
        if (found && found.delta) {
          if (found.delta.type === 'flat') subtotal += Number(found.delta.value || 0);
          if (found.delta.type === 'percent') subtotal += Math.round(base * (Number(found.delta.value || 0) / 100));
        }
      } else if (spec.type === 'range') {
        const val = Number(selected[key] ?? 0);
        const tiers = spec.tiers || [];
        let applied = null;
        for (const t of tiers) {
          if (val <= Number(t.max)) { applied = t; break; }
        }
        if (applied && applied.delta) {
          if (applied.delta.type === 'flat') subtotal += Number(applied.delta.value || 0);
          if (applied.delta.type === 'percent') subtotal += Math.round(base * (Number(applied.delta.value || 0) / 100));
        }
      }
    }
    // Rush fee: if deliveryDate is within 2 days from now, add ₹50
    if (selected.deliveryDate) {
      try {
        const today = new Date();
        const sel = new Date(selected.deliveryDate);
        const diffMs = sel.setHours(0,0,0,0) - today.setHours(0,0,0,0);
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        if (diffDays >= 0 && diffDays <= 2) {
          subtotal += 50;
        }
      } catch {}
    }
    return subtotal;
  }, [rules, selected, artwork?.price]);

  // Emit changes without including onChange in deps to avoid re-stabilizing on each render
  useEffect(() => {
    if (typeof onChange === 'function') {
      onChange({ selectedOptions: selected, total });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selected, total]);

  const handleSelect = (key, value) => {
    setSelected(prev => ({ ...prev, [key]: value }));
  };

  const renderControl = (key, spec) => {
    if (spec.type === 'select') {
      return (
        <select value={selected[key] ?? ''} onChange={e => handleSelect(key, e.target.value)}>
          <option value="">Select {key}</option>
          {(spec.values || []).map(v => (
            <option key={String(v.value)} value={String(v.value)}>
              {String(v.value)} {v?.delta?.value ? `(+${v.delta.type === 'percent' ? v.delta.value + '%' : '₹' + v.delta.value})` : ''}
            </option>
          ))}
        </select>
      );
    }
    if (spec.type === 'range') {
      const max = (spec.tiers || []).slice(-1)[0]?.max ?? 100;
      return (
        <div className="range-field">
          <input type="range" min={0} max={Number(max)} value={Number(selected[key] ?? 0)} onChange={e => handleSelect(key, Number(e.target.value))} />
          <span>{Number(selected[key] ?? 0)} {spec.unit || ''}</span>
        </div>
      );
    }
    return null;
  };

  return (
    <div className="price-calculator">
      {loading && <div className="muted">Loading pricing...</div>}
      {error && <div className="error">{error}</div>}
      {!loading && !error && (
        <>
          <div className="calc-row base">
            <span>Base price</span>
            <strong>₹{Number(rules.base_price ?? artwork?.price ?? 0)}</strong>
          </div>
          {Object.entries(rules.options || {})
            .map(([key, spec]) => (
              <div key={key} className="calc-row option">
                <label style={{ textTransform: 'capitalize' }}>{key}</label>
                {renderControl(key, spec)}
              </div>
            ))}
          <div className="calc-row option">
            <label>Delivery date</label>
            <input type="date" value={selected.deliveryDate || ''} onChange={e => handleSelect('deliveryDate', e.target.value)} />
          </div>
          <div className="calc-row total">
            <span>Total</span>
            <strong>₹{Number(total)}</strong>
          </div>
        </>
      )}
      <style>{`
        .price-calculator { display: flex; flex-direction: column; gap: 10px; }
        .calc-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .calc-row.option { gap: 16px; }
        .calc-row.total { border-top: 1px solid #eee; padding-top: 8px; font-size: 18px; }
        select { padding: 6px 8px; border: 1px solid #e5e7eb; border-radius: 6px; }
        .range-field { display: flex; align-items: center; gap: 8px; }
        .muted { color: #6b7280; }
        .error { color: #b91c1c; }
        input[type="date"] { padding: 6px 8px; border: 1px solid #e5e7eb; border-radius: 6px; }
      `}</style>
    </div>
  );
}


