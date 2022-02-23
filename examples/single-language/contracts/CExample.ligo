/// \namespace CExample
/// Demo project
/// \author Dmitrii Dmitriev
/// \date 02.2022
/// \copyright MIT

#include "CExample/storage.ligo"

type t_entrypoint is
#if ENABLE_OWNER
/// Change owner of contract
| ChangeOwner of t_owner
#endif // ENABLE_OWNER
/// Demo method
| Demo of nat
;

/// Single entrypoint of contract
function main(const entrypoint: t_entrypoint; var s: t_storage): t_return is
case entrypoint of [
#if ENABLE_OWNER
// Change owner of contract
| ChangeOwner(newowner) -> (cNO_OPERATIONS, block { s.owner:= MOwner.accessChange(newowner, s.owner); } with s)
#endif // ENABLE_OWNER
// Demo method
| Demo(inc) -> (cNO_OPERATIONS, block { s.sum := s.sum + inc; } with s)
];
