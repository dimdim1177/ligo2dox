#if !STORAGE_INCLUDED
#define STORAGE_INCLUDED

#include "types.ligo"

///RU Хранилище контракта
///EN Storage of contract
type t_storage is [@layout:comb] record [
#if ENABLE_OWNER
    owner: t_owner;///RU< Владелец контракта ///EN< Owner of contract
#endif // ENABLE_OWNER
    sum: nat;///RU< Сумма ///EN< Sum
];

///RU Тип результата отработки контракта
///EN Type of return by contract
type t_return is t_operations * t_storage;

#endif // !STORAGE_INCLUDED
