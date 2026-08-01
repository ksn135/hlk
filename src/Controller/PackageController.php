<?php

namespace App\Controller;

use App\Entity\Contractor;
use App\Entity\ReviewPackage;
use App\Repository\ReviewPackageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CONTRACTOR')]
class PackageController extends AbstractController
{
    public function __construct(
        private ReviewPackageRepository $packageRepository,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        $contractor = $this->requireContractor();

        return $this->render('package/list.html.twig', [
            'packages' => $this->packageRepository->findActiveForContractor((int) $contractor->getId()),
            'archive' => false,
        ]);
    }

    #[Route('/archive', name: 'app_archive')]
    public function archive(): Response
    {
        $contractor = $this->requireContractor();

        return $this->render('package/list.html.twig', [
            'packages' => $this->packageRepository->findArchiveForContractor((int) $contractor->getId()),
            'archive' => true,
        ]);
    }

    #[Route('/p/{guid}', name: 'app_package_show', requirements: ['guid' => '[0-9a-fA-F\-]{36}'])]
    public function show(string $guid): Response
    {
        $package = $this->requirePackage($guid);
        $package->createLog('opened', 'Контрагент открыл пакет');
        $this->em->flush();

        return $this->render('package/show.html.twig', [
            'package' => $package,
        ]);
    }

    #[Route('/p/{guid}/submit', name: 'app_package_submit', methods: ['POST'], requirements: ['guid' => '[0-9a-fA-F\-]{36}'])]
    public function submit(string $guid, Request $request): Response
    {
        $package = $this->requirePackage($guid);

        if (!$this->isCsrfTokenValid('submit_package_'.$package->getGuid(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Некорректный CSRF-токен.');
        }

        if (!$package->getStatus()->isEditable()) {
            $this->addFlash('error', 'Пакет уже закрыт для редактирования.');

            return $this->redirectToRoute('app_package_show', ['guid' => $package->getGuid()]);
        }

        $package->markSubmitted();
        $package->createLog('submitted', 'Контрагент отправил пакет');
        $this->em->flush();

        $this->addFlash('success', 'Документы отправлены. Пакет перемещён в архив.');

        return $this->redirectToRoute('app_archive');
    }

    private function requireContractor(): Contractor
    {
        $user = $this->getUser();
        if (!$user instanceof Contractor) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function requirePackage(string $guid): ReviewPackage
    {
        $contractor = $this->requireContractor();
        $package = $this->packageRepository->findOneByGuidForContractor($guid, (int) $contractor->getId());
        if (!$package instanceof ReviewPackage) {
            throw $this->createNotFoundException('Пакет не найден.');
        }

        return $package;
    }
}
