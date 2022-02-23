#if !TYPES_INCLUDED
#define TYPES_INCLUDED

#include "config.ligo"
#include "../include/consts.ligo"
#include "../module/MOwner.ligo"

/// Transition type defines
#if ENABLE_OWNER
type t_owner is MOwner.t_owner;
#endif // ENABLE_OWNER

#endif // !TYPES_INCLUDED
