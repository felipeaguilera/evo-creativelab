/**
 * images.js — Netlify Image CDN helper
 *
 * How it works:
 *  - In PRODUCTION (after git push → Netlify build):
 *    URLs are rewritten to /.netlify/images?url=...&w=...&format=webp
 *    Netlify CDN serves WebP/AVIF on the fly, cached at the edge.
 *    The browser picks the best size via srcset/sizes.
 *
 *  - In DEVELOPMENT (astro dev):
 *    Returns the original local path directly.
 *    /.netlify/images is not available in dev mode.
 *
 * Usage in .astro files:
 *
 *   import { netlifyImg, netlifyImgSrcset } from '../utils/images.js';
 *
 *   // Single optimized URL (e.g. for thumbnails):
 *   <img src={netlifyImg(cover, { w: 800 })} />
 *
 *   // Hero with full responsive srcset:
 *   <img
 *     src={netlifyImg(cover, { w: 1280 })}
 *     srcset={netlifyImgSrcset(cover, [640, 960, 1280, 1920, 2560])}
 *     sizes="(max-width: 1280px) 100vw, 1280px"
 *   />
 *
 * Rule (see EVO Creative Lab/MEMORY.md): use only `w` for thumbnails, never
 * `h`/`fit` — let CSS object-fit do the crop. `format=auto` is NOT valid on
 * Netlify Image CDN, only webp | avif | jpg | png | gif | blurhash.
 */

const IS_PROD = import.meta.env.PROD;
const CDN     = '/.netlify/images';

/**
 * Returns a Netlify Image CDN URL in production, or the original src in dev.
 *
 * @param {string}  src    - Local image path, e.g. /assets/images/foo.jpg
 * @param {object}  [opts]
 * @param {number}  [opts.w]       - Output width in pixels
 * @param {string}  [opts.format]  - 'webp' (default) | 'avif' | 'jpg' | 'png' | 'gif'
 * @param {number}  [opts.q]       - Quality 1–100
 * @returns {string}
 */
export function netlifyImg(src, { w, format = 'webp', q } = {}) {
  if (!src) return '';
  // In dev, or for external URLs (http*): return as-is
  if (!IS_PROD || src.startsWith('http')) return src;

  // Build query manually — URLSearchParams encodes "/" as "%2F" which
  // breaks Netlify Image CDN. The url parameter needs literal slashes.
  const parts = [`url=${src}`, `format=${format}`];
  if (w) parts.push(`w=${w}`);
  if (q) parts.push(`q=${q}`);

  return `${CDN}?${parts.join('&')}`;
}

/**
 * Returns a srcset string for responsive images via Netlify Image CDN.
 * Returns undefined in dev (browser just uses the src attribute).
 *
 * @param {string}   src     - Local image path
 * @param {number[]} widths  - Breakpoints, e.g. [640, 960, 1280, 1920, 2560]
 * @param {object}   [opts]  - Same options as netlifyImg (except w, which is set per step)
 * @returns {string|undefined}
 */
export function netlifyImgSrcset(src, widths = [640, 1280, 1920, 2560], opts = {}) {
  if (!src || !IS_PROD || src.startsWith('http')) return undefined;
  return widths
    .map(w => `${netlifyImg(src, { ...opts, w })} ${w}w`)
    .join(', ');
}
