import * as React from 'react';

/** Initials monogram in a hairline frame. Nexus ships no photographic avatars. */
export interface AvatarProps {
  /** Full name - the first two initials are derived from it */
  name?: string;
  /** Square edge in px. 28 in tables, 40 in headers, 64 in dossiers. */
  size?: number;
  /** Corner status dot */
  status?: 'active' | 'pending' | 'inactive';
  /** Brass frame + wash - use for the signed-in user only */
  brass?: boolean;
}
export function Avatar(props: AvatarProps): JSX.Element;
