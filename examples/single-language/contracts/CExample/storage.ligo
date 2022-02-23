#if !STORAGE_INCLUDED
#define STORAGE_INCLUDED

#include "types.ligo"

/// Storage of contract
type t_storage is [@layout:comb] record [
#if ENABLE_OWNER
    owner: t_owner;///< Owner of contract
#endif // ENABLE_OWNER
    sum: nat;///< Sum
];

/// Type of return by contract
type t_return is t_operations * t_storage;

#endif // !STORAGE_INCLUDED
