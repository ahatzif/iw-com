export function money(value) {
  return new Intl.NumberFormat('el-GR', {
    style: 'currency',
    currency: 'EUR',
  }).format(Number(value || 0));
}

export function formatDate(value) {
  const date = new Date(`${value}T00:00:00`);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('el-GR', {
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(date);
}

export function parseYmd(value) {
  if (!value) return null;
  const [year, month, day] = String(value).split('-').map((part) => Number(part));
  if (!year || !month || !day) return null;
  const date = new Date(year, month - 1, day);
  return Number.isNaN(date.getTime()) ? null : date;
}

export function toYmd(date) {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

export function monthTitle(value) {
  const date = typeof value === 'string' ? parseYmd(value) : value;
  if (!date) return '';
  return new Intl.DateTimeFormat('el-GR', {
    month: 'long',
    year: 'numeric',
  }).format(date);
}

export function formatDateTime(value) {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  return new Intl.DateTimeFormat('el-GR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
}

export function timeLabel(slot) {
  if (!slot) return '';
  return `${slot.time}${slot.end ? ` - ${slot.end}` : ''}`;
}

export function slotAvailabilityValue(slot) {
  if (!slot || slot.availability === undefined || slot.availability === null || slot.availability === '') {
    return null;
  }

  const availability = Number(slot.availability);
  return Number.isFinite(availability) ? availability : null;
}

export function slotAvailabilityState(slot) {
  const availability = slotAvailabilityValue(slot);
  if (slot?.status === 'sold-out' || (availability !== null && availability <= 0)) {
    return 'sold-out';
  }
  if (slot?.status === 'limited') {
    return 'low';
  }
  if (availability === 1) {
    return 'low';
  }
  if (availability !== null) {
    return 'available';
  }
  return 'neutral';
}

export function slotAvailabilityLabel(slot) {
  const availability = slotAvailabilityValue(slot);
  const state = slotAvailabilityState(slot);

  if (state === 'sold-out') {
    return 'εξαντλήθηκαν';
  }
  if (availability === 1) {
    return 'απομένει 1';
  }
  if (availability !== null) {
    return `απομένουν ${availability}`;
  }
  return '';
}

export function isSlotBookable(slot) {
  return Boolean(slot && slot.time && slotAvailabilityState(slot) !== 'sold-out');
}
