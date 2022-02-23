#if !CONFIG_INCLUDED
#define CONFIG_INCLUDED

///RU \file
///RU @brief Конфигурация контракта (ключи компиляции)
///RU @attention Файл должен быть подключен в проект ДО всего остального
///EN \file
///EN @brief Configuration of contract (compilation options)
///EN @attention File must be included to project BEFORE all other files

///RU У контракта есть владелец
///RU Владелец обладает всеми правами админа + может сменить владельца
///EN Contract has owner
///EN Owner can do all as admin and change admin
#define ENABLE_OWNER

#endif // !CONFIG_INCLUDED
