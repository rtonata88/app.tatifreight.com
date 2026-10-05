# Assets

**Nexus ships no brand mark.** Nothing in `rtonata88/nexus` is a Nexus logo.

`legacy-template/` holds the three PNGs the running app currently renders
(`public/assets/images/logo/`). They belong to the **Riho admin Bootstrap template** the app was
scaffolded from — they are kept here only as a record of what is on screen today.

**Do not use them.** They are not a brand, and shipping them would be shipping someone else's
template mark.

Until a real mark exists, the system sets the wordmark in type:

- **Wordmark** — "Nexus" in Archivo 800, `letter-spacing: 0.02em`. Rail: 16px. Auth panel: 26px.
  Printed documents: 22px.
- **Monogram** — "NX" in Archivo 800 inside a 1px hairline frame with a brass rule. Use only
  where a square mark is structurally required (favicon, avatar fallback, document seal).

See `guidelines/wordmark.html` for the specimen.

Send a wordmark plus monogram in light and dark and both are replaced in one place.

## Icons

`icons/icon-sprite.svg` is the repo's own `public/assets/svg/icon-sprite.svg` — the
Feather-derived sprite the Blade sidebar references as `icon-sprite.svg#stroke-home` and
`#fill-home`. It is kept for reference during the port (and because the Blade app still needs it
while both stacks run). It is a **template asset, not a Nexus one.**

New icons come from **Lucide**. The UI kits load its UMD build from CDN; production should
install `lucide-react`. Do not extend the sprite.
