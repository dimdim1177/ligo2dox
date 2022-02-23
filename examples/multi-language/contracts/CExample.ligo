///RU \namespace CExample
///RU Демо проект
///RU \author Дмитрий Дмитриев
///EN \namespace CExample
///EN Demo project
///EN \author Dmitrii Dmitriev
/// \date 02.2022
/// \copyright MIT

#include "CExample/storage.ligo"

type t_entrypoint is
#if ENABLE_OWNER
///RU Смена владельца контракта ///EN Change owner of contract
| ChangeOwner of t_owner
#endif // ENABLE_OWNER
///RU Демо метод ///EN Demo method
| Demo of nat
;

///RU Единая точка входа контракта
///EN Single entrypoint of contract
function main(const entrypoint: t_entrypoint; var s: t_storage): t_return is
case entrypoint of [
#if ENABLE_OWNER
//RU Смена владельца контракта //EN Change owner of contract
| ChangeOwner(newowner) -> (cNO_OPERATIONS, block { s.owner:= MOwner.accessChange(newowner, s.owner); } with s)
#endif // ENABLE_OWNER
//RU Демо метод //EN Demo method
| Demo(inc) -> (cNO_OPERATIONS, block { s.sum := s.sum + inc; } with s)
];
