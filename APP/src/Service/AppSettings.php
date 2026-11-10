<?php

namespace App\Service;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Settings changed in the administration; the environment only provides the defaults.
 */
class AppSettings
{
    private const OFFICE_EMAIL = 'office_email';
    private const DIGEST_ENABLED = 'digest_enabled';
    private const DIGEST_HOUR = 'digest_hour';
    private const DEFAULT_DIGEST_HOUR = 17;

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly EntityManagerInterface $em,
        #[Autowire('%office_email%')] private readonly string $defaultOfficeEmail,
    ) {
    }

    /** Where the technicians' answers go (accepted, declined, interested). */
    public function officeEmail(): string
    {
        $value = trim((string) $this->settings->find(self::OFFICE_EMAIL)?->getValue());

        return $value !== '' ? $value : $this->defaultOfficeEmail;
    }

    public function setOfficeEmail(string $email): void
    {
        $setting = $this->settings->find(self::OFFICE_EMAIL) ?? new Setting(self::OFFICE_EMAIL);
        $setting->setValue(trim($email));
        $this->em->persist($setting);
        $this->em->flush();
    }

    /** Whether news for technicians and volunteers is collected into one mail a day. */
    public function digestEnabled(): bool
    {
        // on unless switched off in the administration
        return $this->settings->find(self::DIGEST_ENABLED)?->getValue() !== '0';
    }

    /** Full hour (Vienna time) the digest mails go out. */
    public function digestHour(): int
    {
        $value = $this->settings->find(self::DIGEST_HOUR)?->getValue();

        return is_numeric($value) && (int) $value >= 0 && (int) $value <= 23 ? (int) $value : self::DEFAULT_DIGEST_HOUR;
    }

    public function setDigest(bool $enabled, int $hour): void
    {
        $this->store(self::DIGEST_ENABLED, $enabled ? '1' : '0');
        $this->store(self::DIGEST_HOUR, (string) max(0, min(23, $hour)));
        $this->em->flush();
    }

    private function store(string $name, string $value): void
    {
        $setting = $this->settings->find($name) ?? new Setting($name);
        $setting->setValue($value);
        $this->em->persist($setting);
    }
}
