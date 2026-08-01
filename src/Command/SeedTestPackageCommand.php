<?php

namespace App\Command;

use App\Entity\Contractor;
use App\Entity\ReviewPackage;
use App\Entity\ReviewPackageFile;
use App\Enum\ReviewPackageFileStatus;
use App\Enum\ReviewPackageStatus;
use App\Repository\ContractorRepository;
use App\Service\ReviewPackageFileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'hlk:seed-test-package',
    description: 'Включает ЛК для тестового контрагента и создаёт пакет с копиями docx',
)]
class SeedTestPackageCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private ContractorRepository $contractorRepository,
        private ReviewPackageFileStorage $fileStorage,
        private UserPasswordHasherInterface $passwordHasher,
        private string $appDirsFilesLocal,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('contractor-id', null, InputOption::VALUE_REQUIRED, 'ID контрагента', '2473')
            // ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Пароль ЛК', 'testlk2473')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Путь к исходному docx (или каталог с *.docx)')
            ->addOption('doc-id', null, InputOption::VALUE_REQUIRED, 'Фиктивный doc_id', '2501235')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $contractorId = (int) $input->getOption('contractor-id');
        // $plainPassword = (string) $input->getOption('password');
        $docId = (int) $input->getOption('doc-id');

        $contractor = $this->contractorRepository->find($contractorId);
        if (!$contractor instanceof Contractor) {
            $io->error(sprintf('Контрагент id=%d не найден. Примените SQL полей ЛК и проверьте DATABASE_URL.', $contractorId));

            return Command::FAILURE;
        }

        // if (null === $contractor->getLogin() || '' === $contractor->getLogin()) {
        //     $contractor->setLogin($contractor->getInn() ?: ('contractor'.$contractorId));
        // }
        // $contractor->setAllowLkAccess(true);
        // $contractor->setPasswordPlain($plainPassword);
        // $contractor->setPassword($this->passwordHasher->hashPassword($contractor, $plainPassword));

        $sources = $this->resolveSources($input->getOption('source'));
        if ([] === $sources) {
            $io->error('Не найдены исходные .docx. Укажите --source=/path/to/file.docx или положите файлы в var/fixtures/*.docx');

            return Command::FAILURE;
        }

        $package = new ReviewPackage();
        $package->setContractorId($contractorId);
        $package->setDocId($docId > 0 ? $docId : $contractorId);
        $package->setStatus(ReviewPackageStatus::Active);
        $package->setAttributes([
            ['key' => 'doc_number', 'label' => 'Номер документа', 'value' => 'TEST-HLK-'.$contractorId],
            ['key' => 'initiator', 'label' => 'Инициатор', 'value' => 'Тестовый менеджер'],
            ['key' => 'doc_date', 'label' => 'Дата', 'value' => (new \DateTimeImmutable())->format('d.m.Y')],
            ['key' => 'subject', 'label' => 'Предмет', 'value' => 'Тестовый пакет на согласование'],
            ['key' => 'deadline', 'label' => 'Срок', 'value' => (new \DateTimeImmutable('+14 days'))->format('d.m.Y')],
        ]);

        $slots = ['contract_form', 'primary_annex_form'];
        foreach (array_values($sources) as $index => $sourcePath) {
            $displayName = basename($sourcePath);
            $relative = $this->fileStorage->copyIntoPackage($sourcePath, $package->getGuid(), $displayName);
            $file = new ReviewPackageFile();
            $file->setDisplayName($displayName);
            $file->setFilename($relative);
            $file->setSlotKey($slots[$index] ?? ('slot_'.($index + 1)));
            $file->setStatus(ReviewPackageFileStatus::Editing);
            $package->addFile($file);
        }

        $package->createLog('created', 'Тестовый пакет создан командой hlk:seed-test-package');
        $this->em->persist($package);
        $this->em->flush();

        $io->success([
            sprintf('Контрагент #%d (inn=%s): login=%s', $contractorId, $contractor->getInn(), $contractor->getLogin()),
            sprintf('Пакет guid=%s, файлов=%d', $package->getGuid(), $package->getFiles()->count()),
            sprintf('Файлы в %s/review_packages/%s/', $this->appDirsFilesLocal, $package->getGuid()),
        ]);

        return Command::SUCCESS;
    }

    /** @return list<string> */
    private function resolveSources(mixed $sourceOption): array
    {
        if (\is_string($sourceOption) && '' !== $sourceOption) {
            if (is_file($sourceOption)) {
                return [$sourceOption];
            }
            if (is_dir($sourceOption)) {
                return $this->globDocx($sourceOption);
            }
        }

        $fixtureDir = \dirname(__DIR__, 2).'/var/fixtures';
        if (is_dir($fixtureDir)) {
            $files = $this->globDocx($fixtureDir);
            if ([] !== $files) {
                return \array_slice($files, 0, 2);
            }
        }

        return [];
    }

    /** @return list<string> */
    private function globDocx(string $dir): array
    {
        $files = glob(rtrim($dir, '/').'/*.docx') ?: [];
        sort($files);

        return array_values(array_filter($files, 'is_file'));
    }
}
