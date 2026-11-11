<?php

namespace MaximaPridex\TaskMentions;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}


use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

final class Handler
{
    /**
     * PHP-событие im:OnAfterMessagesAdd.
     * Это совместимый обработчик: $messageId, $messageFields (не объект Event).
     *
     * @param int|string $messageId
     * @param array $messageFields
     */
    public static function onAfterMessagesAdd($messageId, $messageFields)
    {
        if (!is_array($messageFields))
        {
            return;
        }

        // Обрабатываем только реальные сообщения чата ЗАДАЧИ, не другие чаты.
        if (($messageFields['CHAT_ENTITY_TYPE'] ?? '') !== 'TASKS_TASK')
        {
            return;
        }

        if (($messageFields['SYSTEM'] ?? 'N') === 'Y')
        {
            return;
        }

        $taskId = (int)($messageFields['CHAT_ENTITY_ID'] ?? 0);
        $authorId = (int)($messageFields['AUTHOR_ID'] ?? $messageFields['FROM_USER_ID'] ?? 0);
        $message = $messageFields['MESSAGE'] ?? '';

        if ($taskId <= 0 || $authorId <= 0 || !is_string($message) || $message === '')
        {
            return;
        }

        $role = (int)Option::get('maximapridex.taskmentions', 'role', '0');
        if ($role !== 0 && $role !== 1)
        {
            self::logError('Unknown role value; expected 0 or 1.');
            return;
        }

        // Настоящие упоминания Битрикс24 представлены BB-кодом [USER=123]...[/USER].
        // Упоминание "всех" [USER=all] намеренно игнорируется.
        if (!preg_match_all('~\[USER=(\d+)\]~i', $message, $matches))
        {
            return;
        }

        $mentionedIds = array_values(array_unique(array_map('intval', $matches[1])));
        $mentionedIds = array_values(array_filter($mentionedIds, static function ($id) use ($authorId) {
            return $id > 0 && $id !== $authorId;
        }));

        if (!$mentionedIds || !Loader::includeModule('tasks'))
        {
            return;
        }

        try
        {
            // Действуем с правами автора сообщения, без обхода прав доступа.
            $task = \CTaskItem::getInstance($taskId, $authorId);
            $data = $task->getData(false);

            if (!is_array($data)
                || !isset($data['ACCOMPLICES'], $data['AUDITORS'])
                || !is_array($data['ACCOMPLICES'])
                || !is_array($data['AUDITORS']))
            {
                self::logError('Task participant fields are unavailable; task ' . $taskId);
                return;
            }

            $accomplices = array_values(array_unique(array_map('intval', $data['ACCOMPLICES'])));
            $auditors = array_values(array_unique(array_map('intval', $data['AUDITORS'])));

            // Уже существующие роли НЕ меняем (включая наблюдателя в режиме 0).
            $participants = array_fill_keys(array_merge(
                [(int)($data['CREATED_BY'] ?? 0), (int)($data['RESPONSIBLE_ID'] ?? 0)],
                $accomplices,
                $auditors
            ), true);

            $newUsers = [];
            foreach ($mentionedIds as $userId)
            {
                if (isset($participants[$userId]) || !self::isActiveUser($userId))
                {
                    continue;
                }

                $newUsers[] = $userId;
                $participants[$userId] = true;
            }

            if (!$newUsers)
            {
                return;
            }

            // Передаем только изменяемое поле и ВСЕХ ранее назначенных участников.
            $field = ($role === 0) ? 'ACCOMPLICES' : 'AUDITORS';
            $existing = ($role === 0) ? $accomplices : $auditors;
            $result = $task->update([$field => array_merge($existing, $newUsers)]);

            if ($result === false)
            {
                self::logError('Task update returned false; task ' . $taskId . ', message ' . (int)$messageId);
            }
        }
        catch (\Throwable $exception)
        {
            // Ошибки прав доступа и API не должны ломать отправку сообщения.
            self::logError(
                'Task ' . $taskId . ', message ' . (int)$messageId . ': '
                . get_class($exception) . ': ' . $exception->getMessage()
            );
        }
    }

    private static function isActiveUser($userId)
    {
        $result = \CUser::GetByID($userId);
        $user = $result ? $result->Fetch() : false;

        return is_array($user) && ($user['ACTIVE'] ?? 'N') === 'Y';
    }

    private static function logError($message)
    {
        if (function_exists('AddMessage2Log'))
        {
            \AddMessage2Log($message, 'maximapridex.taskmentions');
        }
    }
}
