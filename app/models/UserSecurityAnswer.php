<?php

class UserSecurityAnswer
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function saveAnswers(int $userId, array $answers): bool
    {
        // answers: [question_index => plain_answer]
        try {
            $this->pdo->beginTransaction();

            $delete = $this->pdo->prepare('DELETE FROM user_security_answers WHERE user_id = ?');
            $delete->execute([$userId]);

            $insert = $this->pdo->prepare('INSERT INTO user_security_answers (user_id, question_index, answer_hash) VALUES (?, ?, ?)');

            foreach ($answers as $qIndex => $plain) {
                $plainClean = trim((string)$plain);
                if ($plainClean === '') {
                    continue;
                }
                $hash = password_hash($plainClean, PASSWORD_DEFAULT);
                $insert->execute([$userId, (int)$qIndex, $hash]);
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $ex) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function countUserAnswers(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total FROM user_security_answers WHERE user_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function getUserQuestionIndices(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT question_index FROM user_security_answers WHERE user_id = ?');
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        return array_map(fn($r) => (int)$r['question_index'], $rows ?: []);
    }

    public function getAnswersForIndices(int $userId, array $indices): array
    {
        if (empty($indices)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($indices), '?'));
        $params = array_merge([$userId], $indices);

        $stmt = $this->pdo->prepare('SELECT question_index, answer_hash FROM user_security_answers WHERE user_id = ? AND question_index IN (' . $placeholders . ')');
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['question_index']] = $r['answer_hash'];
        }

        return $map;
    }

    public function verifyAnswers(int $userId, array $provided): bool
    {
        // provided: [question_index => plain_answer]
        $indices = array_map('intval', array_keys($provided));
        $stored = $this->getAnswersForIndices($userId, $indices);

        foreach ($provided as $qIndex => $plain) {
            $plainClean = trim((string)$plain);
            if (!isset($stored[$qIndex]) || $plainClean === '') {
                return false;
            }
            if (!password_verify($plainClean, $stored[$qIndex])) {
                return false;
            }
        }

        return true;
    }
}
