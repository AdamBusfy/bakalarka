<?php
namespace App\Controller;
use App\Entity\Category;
use App\Entity\Item;
use App\Entity\Location;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiSelectController extends AbstractController
{
    /**
     * @Route ("/api/select/locations" , name="api_select_locations")
     * @param Request $request
     * @return Response
     */
    public function locations(Request $request): Response
    {
        $isAdmin = in_array('ROLE_ADMIN', $this->getUser()->getRoles());

        $locationRepository = $this->getDoctrine()
            ->getRepository(Location::class);

        $queryBuilder = $locationRepository->createQueryBuilder('qb')
            ->select('l')
            ->from(Location::class, 'l');

        if (!$isAdmin) {
            $usersLocationsIds = array_map(function (Location $location) {
                return $location->getId();
            }, $this->getUser()->getLocations()->toArray());
            if (!empty($usersLocationsIds)) {
                $queryBuilder->andWhere($queryBuilder->expr()->in('l', $usersLocationsIds));
            }
        }
        $locations = $queryBuilder
            ->andWhere('l.isActive = 1')
            ->andWhere('l.name LIKE :lname')
            ->setParameter(
                'lname',
                "%" . $request->query->get('term') . "%"
            )
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $locations = array_map(function (Location $location) {
            return [
                'id' => $location->getId(),
                'text' => $location->getName()
            ];
        }, $locations);

        $response = new Response();
        $response->setContent(json_encode([
            'results' => $locations,
            'pagination' => [
                'more' => false
            ]
        ]));

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route ("/api/select/categories/" , name="api_select_categories")
     * @param Request $request
     * @return Response
     */
    public function allCategories(Request $request): Response
    {
        $categoryRepository = $this->getDoctrine()
            ->getRepository(Category::class);

        $categories = $categoryRepository->createQueryBuilder('c')
            ->where('c.isActive = 1 ')
            ->andWhere('c.name LIKE :cname')
            ->setParameter(
                'cname',
                "%" . $request->query->get('term') . "%"
            )
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $categories = array_map(function (Category $category) {
            return [
                'id' => $category->getId(),
                'text' => $category->getName()
            ];
        }, $categories);

        $response = new Response();
        $response->setContent(json_encode([
            'results' => $categories,
            'pagination' => [
                'more' => false
            ]
        ]));

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route ("/api/select/items/" , name="api_select_items")
     * @param Request $request
     * @return Response
     */
    public function allItems(Request $request): Response
    {
        $itemRepository = $this->getDoctrine()
            ->getRepository(Item::class);

        $items = $itemRepository->createQueryBuilder('i')
            ->where('i.state = 1 ')
            ->andWhere('i.name LIKE :iname')
            ->setParameter(
                'iname',
                "%" . $request->query->get('term') . "%"
            )
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $items = array_map(function (Item $item) {
            return [
                'id' => $item->getId(),
                'text' => $item->getName()
            ];
        }, $items);

        $response = new Response();
        $response->setContent(json_encode([
            'results' => $items,
            'pagination' => [
                'more' => false
            ]
        ]));

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}