# 07 — Lesson Content Editor

Read `context/AGENTS.md` and all context files before starting.
Depends on: 06-module-lesson-tree.

## Goal
Let an instructor edit a lesson's actual content: rich text body, an optional
video embed URL, and an optional downloadable attachment. No new custom editor —
reuse WordPress's native block editor for the text and add a small meta box for
the two LMS-specific fields.

## Design decisions
- The rich text body is just the lesson post's normal `post_content`, edited via
  the standard block editor at `post.php?post={lesson_id}&action=edit` — **not**
  embedded inside the React course builder. The builder (06) gets an "Edit
  Content →" link per lesson row that opens this native screen in a new tab
  rather than reimplementing a text editor.
- Video URL and attachment are lesson-specific fields the block editor doesn't
  cover — implemented as a classic PHP meta box (not React; this is a simple
  form, doesn't warrant a JS framework per `03-code-standards.md`).
- Video URL: stored as `_glms_video_url` (sanitized with `esc_url_raw` on save),
  supports any oEmbed-compatible URL (YouTube, Vimeo, etc.) — show a live
  preview in the meta box using `wp_oembed_get()`. Actual playback embedding on
  the frontend lesson player is a later unit (Phase 4); this unit only captures
  and validates the URL.
- Attachment: stored as `_glms_attachment_id` (int), selected via the native
  `wp.media()` uploader — restricts selection to files already in the media
  library (no new upload mechanism), validated on save to be an existing
  attachment post ID.

## Implementation
1. `src/Admin/LessonMetaBox.php`:
   - Registers a meta box on the `glms_lesson` edit screen with two fields: a text input for the video URL (plus an oEmbed preview area) and an "Select File" button + filename display for the attachment.
   - `save_post_glms_lesson` handler: nonce check, sanitizes and saves both meta fields, validates the attachment ID actually exists and is an attachment post type before saving (reject silently — don't save garbage).
2. `assets/blocks/lesson-meta-box.js` (or a similarly small standalone file, no build-step framework needed) — enqueued only on the lesson edit screen, wires the `wp.media()` frame to the "Select File" button and updates the hidden input + filename display.
3. Update `06`'s "Edit Content →" link (already scaffolded) to point at `post.php?post={lesson_id}&action=edit`.

## Verification checklist
- [ ] Lesson edit screen shows the meta box with video URL field (+ preview) and attachment picker.
- [ ] Saving persists both meta fields correctly, and reloading the screen shows the saved values.
- [ ] A malformed or malicious video URL (e.g. containing a script tag) is sanitized on save, not stored raw.
- [ ] Attachment picker only allows selecting existing media library items and stores just the ID — no arbitrary file path can be injected.
- [ ] "Edit Content →" link from the course builder opens the correct lesson's native edit screen.
- [ ] No PHP notices on the lesson edit screen.