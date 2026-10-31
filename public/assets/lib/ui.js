export const escape = value => String(value ?? '').replace(/[&<>"']/g, character => ({
  '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
}[character]));

export const mountainDate = () => new Intl.DateTimeFormat('en-CA', {
  timeZone: 'America/Edmonton', year: 'numeric', month: '2-digit', day: '2-digit',
}).format(new Date());

export const stampTime = stamp => stamp
  ? `${stamp.slice(11, 13)}:${stamp.slice(13, 15)}:${stamp.slice(15, 17)} MT`
  : 'Awaiting snapshot';
export const stampLabel = stamp => stamp ? `${stamp.slice(0, 10)} · ${stampTime(stamp)}` : 'Not retrieved yet';
export const stampISO = stamp => stamp ? `${stamp.slice(0, 10)}T${stamp.slice(11, 13)}:${stamp.slice(13, 15)}:${stamp.slice(15, 17)}` : '';

export function imageURL(id, stamp, width) {
  const url = new URL('/awc/image.php', location.origin);
  url.search = new URLSearchParams({ camera: id, stamp, ...(width ? { format: 'webp', w: width } : {}) });
  return `${url.pathname}${url.search}`;
}

export function pictureHTML(camera, stamp, priority = false) {
  const sizes = '(max-width: 600px) calc(100vw - 32px), (max-width: 1000px) 46vw, (max-width: 1500px) 30vw, 25vw';
  const resolvedSizes = `${priority ? '' : 'auto, '}${sizes}`;
  const widths = [400, 640, 840].filter(width => width <= (camera.width ?? 840));
  if (!widths.length) widths.push(400);
  const srcset = widths.map(width => `${escape(imageURL(camera.id, stamp, width))} ${width}w`).join(', ');
  return `<picture><source type="image/webp" srcset="${srcset}" sizes="${resolvedSizes}"><img src="${escape(imageURL(camera.id, stamp))}" width="${camera.width ?? 840}" height="${camera.height ?? 630}" alt="${escape(camera.title)} camera snapshot" loading="${priority ? 'eager' : 'lazy'}" decoding="async" sizes="${resolvedSizes}" ${priority ? 'fetchpriority="high"' : ''}></picture>`;
}

export function validDate(value) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value ?? '')) return false;
  const date = new Date(`${value}T12:00:00Z`);
  return Number.isFinite(date.valueOf()) && date.toISOString().slice(0, 10) === value;
}
