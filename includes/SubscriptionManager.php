<?php
class SubscriptionManager
{
    private $pdo;
    private $user_id;
    private $MAX_FREE_CUSTOM_DESIGNS = 3;
    private $MAX_FREE_MODIFICATIONS = 3;

    public function __construct($pdo, $user_id)
    {
        $this->pdo = $pdo;
        $this->user_id = $user_id;
    }

    public function isSubscribed()
    {
        $stmt = $this->pdo->prepare("
            SELECT status, subscription_type 
            FROM subscriptions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$this->user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result && $result['status'] === 'active' && $result['subscription_type'] === 'premium';
    }

    public function canRequestCustomDesign()
    {
        if ($this->isSubscribed()) {
            return true;
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as request_count 
            FROM custom_template_requests 
            WHERE user_id = ?
        ");
        $stmt->execute([$this->user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['request_count'] < $this->MAX_FREE_CUSTOM_DESIGNS;
    }

    public function canModifyTemplate()
    {
        if ($this->isSubscribed()) {
            return true;
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as modification_count 
            FROM template_modifications 
            WHERE user_id = ?
        ");
        $stmt->execute([$this->user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['modification_count'] < $this->MAX_FREE_MODIFICATIONS;
    }

    public function incrementCustomDesignCount()
    {
        if (!$this->canRequestCustomDesign()) {
            return false;
        }

        return true;
    }

    public function incrementTemplateModificationCount()
    {
        if (!$this->canModifyTemplate()) {
            return false;
        }

        return true;
    }

    public function getRemainingCustomDesigns()
    {
        if ($this->isSubscribed()) {
            return 'unlimited';
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as request_count 
            FROM custom_template_requests 
            WHERE user_id = ?
        ");
        $stmt->execute([$this->user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->MAX_FREE_CUSTOM_DESIGNS - $result['request_count'];
    }

    public function getRemainingModifications()
    {
        if ($this->isSubscribed()) {
            return 'unlimited';
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as modification_count 
            FROM template_modifications 
            WHERE user_id = ?
        ");
        $stmt->execute([$this->user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->MAX_FREE_MODIFICATIONS - $result['modification_count'];
    }

    public function getUsageStatistics()
    {
        $custom_stmt = $this->pdo->prepare("
            SELECT COUNT(*) as request_count 
            FROM custom_template_requests 
            WHERE user_id = ?
        ");
        $custom_stmt->execute([$this->user_id]);
        $custom_result = $custom_stmt->fetch(PDO::FETCH_ASSOC);

        $mod_stmt = $this->pdo->prepare("
            SELECT COUNT(*) as modification_count 
            FROM template_modifications 
            WHERE user_id = ?
        ");
        $mod_stmt->execute([$this->user_id]);
        $mod_result = $mod_stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'custom_design_count' => $custom_result['request_count'],
            'template_modification_count' => $mod_result['modification_count']
        ];
    }
}
?>