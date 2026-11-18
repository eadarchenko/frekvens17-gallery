import { Painting } from "./interface/painting";

// Available formats, shared by every work (text kept from the original project).
export const FORMATS: { name: string, detail: string }[] = [
  { name: 'Original painting', detail: 'Unique, one of a kind. Available upon request.' },
  { name: 'Classic Print · 50 × 70 cm', detail: 'Limited edition of 25 worldwide, signed and numbered by the artist. Museum-quality reproduction on archival fine art paper. Available upon request.' },
  { name: 'Premium Print · 70 × 100 cm', detail: 'Limited edition of 25 worldwide, signed and numbered by the artist. Large-scale museum-quality reproduction on archival fine art paper. Available upon request.' },
];
export const FORMATS_NOTE = 'All prints are delivered flat or rolled in protective tubes, accompanied by a Certificate of Authenticity.';

export const paintings: Painting[] = [
  {
    id: 1,
    title: 'Point of Transition',
    size: '100 × 150 cm · oil on canvas · 2024',
    description: 'The fragile balance of change.\nA crossing between what was\nand what wishes to be.',
    introduction: 'Original painting, Classic Print and Premium Print. All available upon request.',
    photo: 'assets/point of transition.webp',
    photo2: 'assets/point of transitionn.webp',
  },
  {
    id: 2,
    title: 'Unfolding Presence',
    size: '100 × 100 cm · oil on canvas · 2024',
    description: 'A core pulsing with light,\nits layers opening into the breath of being.',
    introduction: 'Original painting, Classic Print and Premium Print. All available upon request.',
    photo: 'assets/undolding presence.webp',
    photo2: 'assets/undolding presencee.webp',
  },
  {
    id: 3,
    title: 'Formation I',
    size: '100 × 150 cm · oil on canvas · 2024',
    description: 'The first movement of becoming.\nForm gathers, shaping the outline of emergence.\nPart of the diptych “Formation”. It may exist on its own or together with Formation II.',
    introduction: 'Original painting, Classic Print and Premium Print. All available upon request.',
    photo: 'assets/diptih.webp',
    photo2: 'assets/diptihh.webp',
  },
  {
    id: 4,
    title: 'Memory of Water',
    size: '100 × 150 cm · oil on canvas · 2024',
    description: 'Liquid remembrance.\nEchoes of movement and time,\nflowing within the present.',
    introduction: 'Original painting, Classic Print and Premium Print. All available upon request.',
    photo: 'assets/memory of water.webp',
    photo2: 'assets/memory of waterr.webp',
  },
  {
    id: 5,
    title: 'Vertical of Infinity',
    size: '100 × 150 cm · oil on canvas · 2024',
    description: 'An endless axis.\nThe meeting of earth and sky,\nunfolding into boundlessness.',
    introduction: 'Original painting, Classic Print and Premium Print. All available upon request.',
    photo: 'assets/vertikal of infinity.webp',
    photo2: 'assets/vertikal of infinityy.webp',
  },
  {
    id: 6,
    title: 'The Ascent Within',
    size: '100 × 150 cm · oil on canvas · 2024',
    description: 'Climbing through inner strata.\nA rise not outward,\nbut deeper into self.',
    introduction: 'Original painting, Classic Print and Premium Print. All available upon request.',
    photo: 'assets/the ascent within.webp',
    photo2: 'assets/the ascent withinn.webp',
  },
  {
    id: 7,
    title: 'The Inner Orchestra',
    size: '100 × 150 cm · oil on canvas · 2024',
    description: 'Invisible harmonies at play.\nEach stroke a note,\neach color a vibration.',
    introduction: 'Original painting, Classic Print and Premium Print. All available upon request.',
    photo: 'assets/the inner orchestra.webp',
    photo2: 'assets/the inner orchestraa.webp',
  },
  {
    id: 8,
    title: 'The Phenomenon',
    size: '100 × 150 cm · oil on canvas · 2024',
    description: 'An occurrence without a name.\nWhere perception trembles,\nand presence unfolds.',
    introduction: 'Original painting, Classic Print and Premium Print. All available upon request.',
    photo: 'assets/the phenomenon.webp',
    photo2: 'assets/the phenomenonn.webp',
  },
];
