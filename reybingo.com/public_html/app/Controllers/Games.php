<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\PaymentsModel;
use App\Models\DepositsModel;
use App\Models\RetiresModel;
use App\Models\TransfersModel;
use App\Models\ReferralsModel;
use App\Models\RoulettesModel;
use App\Models\GamesModel;
use App\Models\GameRoomsModel;
use App\Models\BoardsModel;
use App\Models\CartonsModel;
use App\Models\NumbersCartonsModel;
use App\Models\ModalitiesModel;
use App\Models\AwardsModel;
use App\Models\SingsModel;
use App\Models\ContactsModel;
use App\Models\NotificationsModel;
//use App\Libraries\PushNotificationService;
use App\Libraries\SimplePushService;
use CodeIgniter\Controller;

class Games extends Controller {
    public function __construct() {
        helper(['form', 'url', 'cookie', 'text']);
        session();
    }
    
    public function index() {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }
        
        $modelUsers = new UsersModel();
        $modelGames = new GamesModel();
        $modelGameRooms = new GameRoomsModel();
        $modelContacts = new ContactsModel();

        $contacts = $modelContacts->findAll();

        $user = $modelUsers->find(session()->get('id'));

        $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');

        $modelGames = new GamesModel();

        $dates = $modelGames->select('date')->distinct()->orderBy('date', 'asc')->findColumn('date');
        $status = [
            'all' => translate('all status'),
            'unstarted' => translate('unstarted'),
            'initiated' => translate('initiated'),
            'finished'  => translate('finished'),
        ];

        $rooms = $modelGameRooms->where('status', 1)->findAll();

        $data = [
            'page' => [
                'title' => translate('list of') . ' ' . translate('games')
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('games/index', ['contacts' => $contacts, 'dates' => $dates, 'status' => $status, 'rooms' => $rooms, 'user' => $user, 'imagePath' => $imagePath]) 
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }

    /*public function index() {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }
        
        $modelUsers = new UsersModel();
        $modelGames = new GamesModel();
        $modelContacts = new ContactsModel();

        if ($game) {
            return redirect()->to('/board');
        }

        $contacts = $modelContacts->findAll();

        $user = $modelUsers->find(session()->get('id'));
        
        $game = $modelGames->find(session()->get('game_id'));

        $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');

        $data = [
            'page' => [
                'title' => translate('list of') . ' ' . translate('games')
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('games/index', ['contacts' => $contacts, 'user' => $user, 'imagePath' => $imagePath]) 
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }*/

    public function add($gameId = null) {
        $modelUsers       = new UsersModel();
        $modelGames       = new GamesModel();
        $modelModalities  = new ModalitiesModel();
        $modelSings       = new SingsModel();
        $modelAwards      = new AwardsModel();
        $modelBoards      = new BoardsModel();
        $modelContacts    = new ContactsModel();
        $modelGameRooms   = new GameRoomsModel();

        $data['modalities'] = $modelModalities->where('status', 1)->findAll();
        $data['gamerooms']  = $modelGameRooms->where('status', 1)->findAll();
        $data['game']       = 1;

        if ($gameId) {
            $data['gameData'] = $modelGames->find($gameId);
            if (!$data['gameData']) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('Juego no encontrado');
            }

            $game = $data['gameData'];

            $data['cover'] = !empty($game['cover']) ? site_url('uploads/covers/' . $game['cover']) : site_url('uploads/covers/image.jpg');

            $data['awards'] = $modelAwards->select('awards.id, awards.modality, awards.amount, awards.observation, modalities.name as modality_name')->join('modalities', 'modalities.id = awards.modality', 'left')->where('awards.game', $gameId)->findAll();

            foreach ($data['awards'] as &$aw) {
                $aw['modality_name'] = translate($aw['modality_name']);
            }
            unset($aw);

            $data['isUpdate'] = true;
        } else {
            $data['gameData'] = null;
            $data['awards']   = [];
            $data['isUpdate'] = false;
            $data['cover']    = site_url('uploads/covers/image.jpg');
        }

        return view('games/add', $data);
    }

    public function addmodality() {
        $modelUsers      = new UsersModel();
        $modelGames      = new GamesModel();
        $modelModalities = new ModalitiesModel();
        $modelSings      = new SingsModel();
        $modelAwards     = new AwardsModel();
        $modelBoards     = new BoardsModel();
        $modelContacts   = new ContactsModel();

        $data['modalities'] = $modelModalities->where('status', 1)->findAll();

        return view('games/modality', $data);
    }

    public function statisticsView() {
        $modelUsers = new UsersModel();
        $modelPayments = new PaymentsModel();
        $modelDeposits = new DepositsModel();
        $modelRetires = new RetiresModel();
        $modelTransfers = new TransfersModel();
        $modelReferrals = new ReferralsModel();
        $modelRoulettes = new RoulettesModel();
        $modelGames = new GamesModel();
        $modelBoards = new BoardsModel();
        $modelCartons = new CartonsModel();
        $modelAwards = new AwardsModel();
        $modelSings = new SingsModel();
        $modelGameRooms = new GameRoomsModel();
        $modelModalities = new ModalitiesModel();
        
        // Obtener fechas de juegos
        $dates = $modelGames->select('date')->distinct()->orderBy('date', 'asc')->findColumn('date');
        
        // Definir estados
        $status = [
            'all' => translate('all status'),
            'unstarted' => translate('unstarted'),
            'initiated' => translate('initiated'),
            'finished' => translate('finished'),
        ];
        
        // Definir módulos
        $modules = [
            'games' => translate('games'),
            'users' => translate('users'),
            'deposits' => translate('deposits'),
            'retires' => translate('retires'),
            'roulette' => translate('roulette'),
            'referrals' => translate('referrals'),
        ];
        
        // Definir tipos de juego
        $gameTypes = [
            '1' => translate('automatic'),
            '2' => translate('manual'),
            '3' => translate('live'),
            '4' => translate('video'),
        ];
        
        // Obtener salas
        $rooms = $modelGameRooms->where('status', 1)->findAll();
        
        // Obtener modalidades
        $modalities = $modelModalities->where('status', 1)->findAll();
        
        $data['users'] = $modelUsers->findAll();
        $data['dates'] = $dates;
        $data['status'] = $status;
        $data['modules'] = $modules;
        $data['rooms'] = $rooms;
        $data['modalities'] = $modalities;
        $data['gameTypes'] = $gameTypes;
        
        return view('games/modalStatistics', $data);
    }

    public function statisticsGet($moduleFilter = 'games', $dateFilter = 'all', $statusFilter = 'all', $roomFilter = 'all', $awardFilter = 'all') {
        $modelUsers = new UsersModel();
        $modelPayments = new PaymentsModel();
        $modelDeposits = new DepositsModel();
        $modelRetires = new RetiresModel();
        $modelTransfers = new TransfersModel();
        $modelReferrals = new ReferralsModel();
        $modelRoulettes = new RoulettesModel();
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelAwards = new AwardsModel();
        $modelBoards = new BoardsModel();
        $modelSings = new SingsModel();
        $modelGameRooms = new GameRoomsModel();
        
        // Obtener parámetros adicionales
        $gameFilter = $this->request->getGet('gamefilter') ?? 'all';
        $startDate = $this->request->getGet('startdate') ?? date('Y-m-01');
        $endDate = $this->request->getGet('enddate') ?? date('Y-m-d');
        $activeTab = $this->request->getGet('activeTab') ?? 'summary';
        
        $data = [];
        
        // PASAR LAS VARIABLES DE FILTROS A LA VISTA
        $data['dateFilter'] = $dateFilter;
        $data['startDate'] = $startDate;
        $data['endDate'] = $endDate;
        $data['activeTab'] = $activeTab;
        
        // Estadísticas generales para el resumen
        if ($activeTab == 'summary' || $activeTab == 'all') {
            // Total usuarios
            $data['total_users'] = $modelUsers->where('status', 1)->where('group', 0)->countAllResults();

            // Comparación con período anterior
            if ($dateFilter != 'all' || ($startDate && $endDate)) {
                // Calcular período anterior
                switch ($dateFilter) {
                    case 'today':
                        $previousStart = date('Y-m-d', strtotime('-1 day'));
                        $previousEnd   = date('Y-m-d', strtotime('-1 day'));
                        break;
                    case 'week':
                        $previousStart = date('Y-m-d', strtotime('-2 weeks'));
                        $previousEnd   = date('Y-m-d', strtotime('-1 week'));
                        break;
                    case 'month':
                        $previousStart = date('Y-m-d', strtotime('-2 months'));
                        $previousEnd   = date('Y-m-d', strtotime('-1 month'));
                        break;
                    case 'year':
                        $previousStart = date('Y-m-d', strtotime('-2 years'));
                        $previousEnd   = date('Y-m-d', strtotime('-1 year'));
                        break;
                    default:
                        // Rango personalizado
                        $days = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
                        $previousStart = date('Y-m-d', strtotime($startDate . ' -' . ($days + 1) . ' days'));
                        $previousEnd   = date('Y-m-d', strtotime($startDate . ' -1 day'));
                        break;
                }

                // Crear nuevo modelo para consulta anterior
                $modelUsersPrevious = new UsersModel();
                $previousUsers = $modelUsersPrevious
                    ->where('status', 1)
                    ->where('group', 0)
                    ->where('created_at >=', $previousStart . ' 00:00:00')
                    ->where('created_at <=', $previousEnd   . ' 23:59:59')
                    ->countAllResults();

                $data['previous_users'] = $previousUsers;

                // Calcular porcentaje de cambio
                if ($previousUsers > 0) {
                    $data['users_change_percent'] = round((($data['total_users'] - $previousUsers) / $previousUsers) * 100, 1);
                    $data['users_trend'] = $data['total_users'] >= $previousUsers ? 'up' : 'down';
                } else {
                    $data['users_change_percent'] = $data['total_users'] > 0 ? 100 : 0;
                    $data['users_trend'] = 'up';
                }
            }
            
            // Total en billeteras
            $data['total_wallet'] = $modelUsers->selectSum('wallet')->where('status', 1)->get()->getRow()->wallet ?? 0;

            // Comparación con período anterior para wallets
            if ($dateFilter != 'all' || ($startDate && $endDate)) {
                // Usar las mismas fechas calculadas anteriormente
                $modelWalletPrevious = new UsersModel();
                
                // Para wallets, necesitamos usuarios que existían en el período anterior
                $previousWallet = $modelWalletPrevious
                    ->selectSum('wallet')
                    ->where('status', 1)
                    ->where('created_at <=', $previousEnd . ' 23:59:59')
                    ->get()
                    ->getRow()
                    ->wallet ?? 0;

                $data['previous_wallet'] = $previousWallet;

                // Calcular porcentaje de cambio
                if ($previousWallet > 0) {
                    $data['wallet_change_percent'] = round((($data['total_wallet'] - $previousWallet) / $previousWallet) * 100, 1);
                    $data['wallet_trend'] = $data['total_wallet'] >= $previousWallet ? 'up' : 'down';
                } else {
                    $data['wallet_change_percent'] = $data['total_wallet'] > 0 ? 100 : 0;
                    $data['wallet_trend'] = 'up';
                }
            }
            
            // Total de depósitos
            $builderDeposits = $modelDeposits;
            $builderDepositsCount = clone $modelDeposits;

            // Aplicar filtros de fecha para deposits
            if ($dateFilter != 'all') {
                switch ($dateFilter) {
                    case 'today':
                        $builderDeposits->where('DATE(created_at)', date('Y-m-d'));
                        $builderDepositsCount->where('DATE(created_at)', date('Y-m-d'));
                        break;
                    case 'week':
                        $builderDeposits->where('created_at >=', date('Y-m-d', strtotime('-1 week')));
                        $builderDepositsCount->where('created_at >=', date('Y-m-d', strtotime('-1 week')));
                        break;
                    case 'month':
                        $builderDeposits->where('created_at >=', date('Y-m-d', strtotime('-1 month')));
                        $builderDepositsCount->where('created_at >=', date('Y-m-d', strtotime('-1 month')));
                        break;
                    case 'year':
                        $builderDeposits->where('created_at >=', date('Y-m-d', strtotime('-1 year')));
                        $builderDepositsCount->where('created_at >=', date('Y-m-d', strtotime('-1 year')));
                        break;
                }
            } elseif ($startDate && $endDate) {
                $builderDeposits->where('created_at >=', $startDate . ' 00:00:00')
                                ->where('created_at <=', $endDate . ' 23:59:59');
                $builderDepositsCount->where('created_at >=', $startDate . ' 00:00:00')
                                     ->where('created_at <=', $endDate . ' 23:59:59');
            }

            $data['total_deposits'] = $builderDeposits
                ->selectSum('amount')
                ->where('status', 1)
                ->get()
                ->getRow()
                ->amount ?? 0;

            $data['count_deposits'] = $builderDepositsCount
                ->where('status', 1)
                ->countAllResults();

            // Comparación con período anterior para deposits
            if ($dateFilter != 'all' || ($startDate && $endDate)) {
                $modelDepositsPrevious = new DepositsModel();
                $modelDepositsCountPrevious = new DepositsModel();
                
                $previousDeposits = $modelDepositsPrevious
                    ->selectSum('amount')
                    ->where('status', 1)
                    ->where('created_at >=', $previousStart . ' 00:00:00')
                    ->where('created_at <=', $previousEnd . ' 23:59:59')
                    ->get()
                    ->getRow()
                    ->amount ?? 0;

                $previousDepositsCount = $modelDepositsCountPrevious
                    ->where('status', 1)
                    ->where('created_at >=', $previousStart . ' 00:00:00')
                    ->where('created_at <=', $previousEnd . ' 23:59:59')
                    ->countAllResults();

                $data['previous_deposits'] = $previousDeposits;
                $data['previous_deposits_count'] = $previousDepositsCount;

                // Calcular porcentaje de cambio
                if ($previousDeposits > 0) {
                    $data['deposits_change_percent'] = round((($data['total_deposits'] - $previousDeposits) / $previousDeposits) * 100, 1);
                    $data['deposits_trend'] = $data['total_deposits'] >= $previousDeposits ? 'up' : 'down';
                } else {
                    $data['deposits_change_percent'] = $data['total_deposits'] > 0 ? 100 : 0;
                    $data['deposits_trend'] = 'up';
                }
            }
            
            // Total de retiros
            $builderRetires = $modelRetires;
            $builderRetiresCount = clone $modelRetires;

            // Aplicar filtros de fecha para retires
            if ($dateFilter != 'all') {
                switch ($dateFilter) {
                    case 'today':
                        $builderRetires->where('DATE(created_at)', date('Y-m-d'));
                        $builderRetiresCount->where('DATE(created_at)', date('Y-m-d'));
                        break;
                    case 'week':
                        $builderRetires->where('created_at >=', date('Y-m-d', strtotime('-1 week')));
                        $builderRetiresCount->where('created_at >=', date('Y-m-d', strtotime('-1 week')));
                        break;
                    case 'month':
                        $builderRetires->where('created_at >=', date('Y-m-d', strtotime('-1 month')));
                        $builderRetiresCount->where('created_at >=', date('Y-m-d', strtotime('-1 month')));
                        break;
                    case 'year':
                        $builderRetires->where('created_at >=', date('Y-m-d', strtotime('-1 year')));
                        $builderRetiresCount->where('created_at >=', date('Y-m-d', strtotime('-1 year')));
                        break;
                }
            } elseif ($startDate && $endDate) {
                $builderRetires->where('created_at >=', $startDate . ' 00:00:00')
                               ->where('created_at <=', $endDate . ' 23:59:59');
                $builderRetiresCount->where('created_at >=', $startDate . ' 00:00:00')
                                    ->where('created_at <=', $endDate . ' 23:59:59');
            }

            $data['total_retires'] = $builderRetires
                ->selectSum('amount')
                ->where('status', 1)
                ->get()
                ->getRow()
                ->amount ?? 0;

            $data['count_retires'] = $builderRetiresCount
                ->where('status', 1)
                ->countAllResults();

            // Comparación con período anterior para retires
            if ($dateFilter != 'all' || ($startDate && $endDate)) {
                $modelRetiresPrevious = new RetiresModel();
                $modelRetiresCountPrevious = new RetiresModel();
                
                $previousRetires = $modelRetiresPrevious
                    ->selectSum('amount')
                    ->where('status', 1)
                    ->where('created_at >=', $previousStart . ' 00:00:00')
                    ->where('created_at <=', $previousEnd . ' 23:59:59')
                    ->get()
                    ->getRow()
                    ->amount ?? 0;

                $previousRetiresCount = $modelRetiresCountPrevious
                    ->where('status', 1)
                    ->where('created_at >=', $previousStart . ' 00:00:00')
                    ->where('created_at <=', $previousEnd . ' 23:59:59')
                    ->countAllResults();

                $data['previous_retires'] = $previousRetires;
                $data['previous_retires_count'] = $previousRetiresCount;

                // Calcular porcentaje de cambio
                if ($previousRetires > 0) {
                    $data['retires_change_percent'] = round((($data['total_retires'] - $previousRetires) / $previousRetires) * 100, 1);
                    $data['retires_trend'] = $data['total_retires'] >= $previousRetires ? 'up' : 'down';
                } else {
                    $data['retires_change_percent'] = $data['total_retires'] > 0 ? 100 : 0;
                    $data['retires_trend'] = 'up';
                }
            }
            
            // Total de juegos
            $builderGames = $modelGames;

            // Aplicar filtros de fecha si están definidos
            if ($dateFilter != 'all') {
                switch ($dateFilter) {
                    case 'today':
                        $builderGames->where('DATE(created_at)', date('Y-m-d'));
                        break;
                    case 'week':
                        $builderGames->where('created_at >=', date('Y-m-d', strtotime('-1 week')));
                        break;
                    case 'month':
                        $builderGames->where('created_at >=', date('Y-m-d', strtotime('-1 month')));
                        break;
                    case 'year':
                        $builderGames->where('created_at >=', date('Y-m-d', strtotime('-1 year')));
                        break;
                }
            } elseif ($startDate && $endDate) {
                $builderGames->where('created_at >=', $startDate . ' 00:00:00')
                             ->where('created_at <=', $endDate . ' 23:59:59');
            }

            $data['total_games'] = $builderGames->countAllResults();

            // Comparación con período anterior para games
            if ($dateFilter != 'all' || ($startDate && $endDate)) {
                // Usar las mismas fechas calculadas anteriormente para el período anterior
                $modelGamesPrevious = new GamesModel();
                
                $previousGames = $modelGamesPrevious
                    ->where('created_at >=', $previousStart . ' 00:00:00')
                    ->where('created_at <=', $previousEnd . ' 23:59:59')
                    ->countAllResults();

                $data['previous_games'] = $previousGames;

                // Calcular porcentaje de cambio
                if ($previousGames > 0) {
                    $data['games_change_percent'] = round((($data['total_games'] - $previousGames) / $previousGames) * 100, 1);
                    $data['games_trend'] = $data['total_games'] >= $previousGames ? 'up' : 'down';
                } else {
                    $data['games_change_percent'] = $data['total_games'] > 0 ? 100 : 0;
                    $data['games_trend'] = 'up';
                }
            }

            // Total de cartones vendidos
            $data['total_cartons'] = $modelCartons->where('user !=', 0)->countAllResults();

            // Usar esto:
            $data['sold_cartons'] = $modelCartons->where('user !=', 0)->countAllResults();
            $data['unsold_cartons'] = $modelCartons->where('user', 0)->countAllResults();
            $data['total_cartons'] = $data['sold_cartons'] + $data['unsold_cartons'];

            // O si quieres aplicar filtros también a los cartones:
            if ($dateFilter != 'all' || ($startDate && $endDate) || $roomFilter != 'all' || $gameFilter != 'all') {
                // Construir query base para cartones
                $cartonBuilder = $modelCartons->select('cartons.*')
                                             ->join('games', 'games.id = cartons.game');
                
                // Aplicar los mismos filtros que a los juegos
                if ($dateFilter != 'all') {
                    $cartonBuilder = $cartonBuilder->where('games.date', $dateFilter);
                } else if ($startDate && $endDate) {
                    $cartonBuilder = $cartonBuilder->where('games.date >=', $startDate)
                                                  ->where('games.date <=', $endDate);
                }
                
                if ($roomFilter != 'all') {
                    $cartonBuilder = $cartonBuilder->where('games.room', $roomFilter);
                }
                
                if ($gameFilter != 'all') {
                    $cartonBuilder = $cartonBuilder->where('games.type', $gameFilter);
                }
                
                // Cartones vendidos (con filtros)
                $data['sold_cartons'] = $cartonBuilder->where('cartons.user !=', 0)->countAllResults();
                
                // Reiniciar builder para cartones no vendidos
                $cartonBuilder = $modelCartons->select('cartons.*')
                                             ->join('games', 'games.id = cartons.game');
                
                // Aplicar filtros nuevamente
                if ($dateFilter != 'all') {
                    $cartonBuilder = $cartonBuilder->where('games.date', $dateFilter);
                } else if ($startDate && $endDate) {
                    $cartonBuilder = $cartonBuilder->where('games.date >=', $startDate)
                                                  ->where('games.date <=', $endDate);
                }
                
                if ($roomFilter != 'all') {
                    $cartonBuilder = $cartonBuilder->where('games.room', $roomFilter);
                }
                
                if ($gameFilter != 'all') {
                    $cartonBuilder = $cartonBuilder->where('games.type', $gameFilter);
                }
                
                // Cartones no vendidos (con filtros)
                $data['unsold_cartons'] = $cartonBuilder->where('cartons.user', 0)->countAllResults();
                
            } else {
                // Sin filtros
                $data['sold_cartons'] = $modelCartons->where('user !=', 0)->countAllResults();
                $data['unsold_cartons'] = $modelCartons->where('user', 0)->countAllResults();
            }

            $data['total_cartons'] = $data['sold_cartons'] + $data['unsold_cartons'];
            
            // Total de premios
            $totalAwards = 0;
            $builderGamesAwards = $modelGames;

            // Aplicar filtros de fecha para awards
            if ($dateFilter != 'all') {
                switch ($dateFilter) {
                    case 'today':
                        $builderGamesAwards->where('DATE(created_at)', date('Y-m-d'));
                        break;
                    case 'week':
                        $builderGamesAwards->where('created_at >=', date('Y-m-d', strtotime('-1 week')));
                        break;
                    case 'month':
                        $builderGamesAwards->where('created_at >=', date('Y-m-d', strtotime('-1 month')));
                        break;
                    case 'year':
                        $builderGamesAwards->where('created_at >=', date('Y-m-d', strtotime('-1 year')));
                        break;
                }
            } elseif ($startDate && $endDate) {
                $builderGamesAwards->where('created_at >=', $startDate . ' 00:00:00')
                                   ->where('created_at <=', $endDate . ' 23:59:59');
            }

            $games = $builderGamesAwards->findAll();

            foreach ($games as $game) {
                $awards = $modelAwards->where('game', $game['id'])->where('status', 1)->findAll();
                $cartons = $modelCartons->where('game', $game['id'])->where('user !=', 0)->countAllResults();
                $accumulated = $cartons * $game['price'];
                $gameAccumulated = $accumulated - ($accumulated * (systemGet('rateEarnings') / 100));
                
                foreach ($awards as $award) {
                    if ($game['award'] == 2) { // Monto fijo
                        $totalAwards += $award['amount'];
                    } else { // Porcentaje del acumulado
                        $totalAwards += $gameAccumulated * $award['amount'] / 100;
                    }
                }
            }

            $data['total_awards'] = $totalAwards;

            // Comparación con período anterior para awards
            if ($dateFilter != 'all' || ($startDate && $endDate)) {
                $totalAwardsPrevious = 0;
                $modelGamesPrevious = new GamesModel();
                
                $gamesPrevious = $modelGamesPrevious
                    ->where('created_at >=', $previousStart . ' 00:00:00')
                    ->where('created_at <=', $previousEnd . ' 23:59:59')
                    ->findAll();

                foreach ($gamesPrevious as $game) {
                    $awards = $modelAwards->where('game', $game['id'])->where('status', 1)->findAll();
                    $cartons = $modelCartons->where('game', $game['id'])->where('user !=', 0)->countAllResults();
                    $accumulated = $cartons * $game['price'];
                    $gameAccumulated = $accumulated - ($accumulated * (systemGet('rateEarnings') / 100));
                    
                    foreach ($awards as $award) {
                        if ($game['award'] == 2) { // Monto fijo
                            $totalAwardsPrevious += $award['amount'];
                        } else { // Porcentaje del acumulado
                            $totalAwardsPrevious += $gameAccumulated * $award['amount'] / 100;
                        }
                    }
                }

                $data['previous_awards'] = $totalAwardsPrevious;

                // Calcular porcentaje de cambio
                if ($totalAwardsPrevious > 0) {
                    $data['awards_change_percent'] = round((($data['total_awards'] - $totalAwardsPrevious) / $totalAwardsPrevious) * 100, 1);
                    $data['awards_trend'] = $data['total_awards'] >= $totalAwardsPrevious ? 'up' : 'down';
                } else {
                    $data['awards_change_percent'] = $data['total_awards'] > 0 ? 100 : 0;
                    $data['awards_trend'] = 'up';
                }
            }
            
            // Total de ruleta
            $builderRoulettes = $modelRoulettes;
            $builderRoulettesCount = clone $modelRoulettes;

            // Aplicar filtros de fecha para roulettes
            if ($dateFilter != 'all') {
                switch ($dateFilter) {
                    case 'today':
                        $builderRoulettes->where('DATE(created_at)', date('Y-m-d'));
                        $builderRoulettesCount->where('DATE(created_at)', date('Y-m-d'));
                        break;
                    case 'week':
                        $builderRoulettes->where('created_at >=', date('Y-m-d', strtotime('-1 week')));
                        $builderRoulettesCount->where('created_at >=', date('Y-m-d', strtotime('-1 week')));
                        break;
                    case 'month':
                        $builderRoulettes->where('created_at >=', date('Y-m-d', strtotime('-1 month')));
                        $builderRoulettesCount->where('created_at >=', date('Y-m-d', strtotime('-1 month')));
                        break;
                    case 'year':
                        $builderRoulettes->where('created_at >=', date('Y-m-d', strtotime('-1 year')));
                        $builderRoulettesCount->where('created_at >=', date('Y-m-d', strtotime('-1 year')));
                        break;
                }
            } elseif ($startDate && $endDate) {
                $builderRoulettes->where('created_at >=', $startDate . ' 00:00:00')
                                 ->where('created_at <=', $endDate . ' 23:59:59');
                $builderRoulettesCount->where('created_at >=', $startDate . ' 00:00:00')
                                      ->where('created_at <=', $endDate . ' 23:59:59');
            }

            $data['total_roulette'] = $builderRoulettes
                ->selectSum('amount')
                ->where('status', 1)
                ->get()
                ->getRow()
                ->amount ?? 0;

            $data['count_roulette'] = $builderRoulettesCount
                ->where('status', 1)
                ->countAllResults();

            // Comparación con período anterior para roulettes
            if ($dateFilter != 'all' || ($startDate && $endDate)) {
                $modelRoulettesPrevious = new RoulettesModel();
                $modelRoulettesCountPrevious = new RoulettesModel();
                
                $previousRoulettes = $modelRoulettesPrevious
                    ->selectSum('amount')
                    ->where('status', 1)
                    ->where('created_at >=', $previousStart . ' 00:00:00')
                    ->where('created_at <=', $previousEnd . ' 23:59:59')
                    ->get()
                    ->getRow()
                    ->amount ?? 0;

                $previousRoulettesCount = $modelRoulettesCountPrevious
                    ->where('status', 1)
                    ->where('created_at >=', $previousStart . ' 00:00:00')
                    ->where('created_at <=', $previousEnd . ' 23:59:59')
                    ->countAllResults();

                $data['previous_roulette'] = $previousRoulettes;
                $data['previous_roulette_count'] = $previousRoulettesCount;

                // Calcular porcentaje de cambio
                if ($previousRoulettes > 0) {
                    $data['roulette_change_percent'] = round((($data['total_roulette'] - $previousRoulettes) / $previousRoulettes) * 100, 1);
                    $data['roulette_trend'] = $data['total_roulette'] >= $previousRoulettes ? 'up' : 'down';
                } else {
                    $data['roulette_change_percent'] = $data['total_roulette'] > 0 ? 100 : 0;
                    $data['roulette_trend'] = 'up';
                }
            }
            
            // Total de referidos
            $builderReferrals = $modelReferrals;
            $builderReferralsCount = clone $modelReferrals;

            // Aplicar filtros de fecha para referrals
            if ($dateFilter != 'all') {
                switch ($dateFilter) {
                    case 'today':
                        $builderReferrals->where('DATE(created_at)', date('Y-m-d'));
                        $builderReferralsCount->where('DATE(created_at)', date('Y-m-d'));
                        break;
                    case 'week':
                        $builderReferrals->where('created_at >=', date('Y-m-d', strtotime('-1 week')));
                        $builderReferralsCount->where('created_at >=', date('Y-m-d', strtotime('-1 week')));
                        break;
                    case 'month':
                        $builderReferrals->where('created_at >=', date('Y-m-d', strtotime('-1 month')));
                        $builderReferralsCount->where('created_at >=', date('Y-m-d', strtotime('-1 month')));
                        break;
                    case 'year':
                        $builderReferrals->where('created_at >=', date('Y-m-d', strtotime('-1 year')));
                        $builderReferralsCount->where('created_at >=', date('Y-m-d', strtotime('-1 year')));
                        break;
                }
            } elseif ($startDate && $endDate) {
                $builderReferrals->where('created_at >=', $startDate . ' 00:00:00')
                                 ->where('created_at <=', $endDate . ' 23:59:59');
                $builderReferralsCount->where('created_at >=', $startDate . ' 00:00:00')
                                      ->where('created_at <=', $endDate . ' 23:59:59');
            }

            $data['total_referrals'] = $builderReferrals
                ->selectSum('amount')
                ->where('status', 1)
                ->get()
                ->getRow()
                ->amount ?? 0;

            $data['count_referrals'] = $builderReferralsCount
                ->where('status', 1)
                ->countAllResults();

            // Comparación con período anterior para referrals
            if ($dateFilter != 'all' || ($startDate && $endDate)) {
                $modelReferralsPrevious = new ReferralsModel();
                $modelReferralsCountPrevious = new ReferralsModel();
                
                $previousReferrals = $modelReferralsPrevious
                    ->selectSum('amount')
                    ->where('status', 1)
                    ->where('created_at >=', $previousStart . ' 00:00:00')
                    ->where('created_at <=', $previousEnd . ' 23:59:59')
                    ->get()
                    ->getRow()
                    ->amount ?? 0;

                $previousReferralsCount = $modelReferralsCountPrevious
                    ->where('status', 1)
                    ->where('created_at >=', $previousStart . ' 00:00:00')
                    ->where('created_at <=', $previousEnd . ' 23:59:59')
                    ->countAllResults();

                $data['previous_referrals'] = $previousReferrals;
                $data['previous_referrals_count'] = $previousReferralsCount;

                // Calcular porcentaje de cambio
                if ($previousReferrals > 0) {
                    $data['referrals_change_percent'] = round((($data['total_referrals'] - $previousReferrals) / $previousReferrals) * 100, 1);
                    $data['referrals_trend'] = $data['total_referrals'] >= $previousReferrals ? 'up' : 'down';
                } else {
                    $data['referrals_change_percent'] = $data['total_referrals'] > 0 ? 100 : 0;
                    $data['referrals_trend'] = 'up';
                }
            }
        }
        
        // Estadísticas específicas según el módulo seleccionado
        switch ($moduleFilter) {
            case 'games':
                if ($activeTab == 'games' || $activeTab == 'summary') {
                    $builder = $modelGames;
                    
                    // Filtrar por fecha
                    if ($dateFilter != 'all') {
                        $builder = $builder->where('date', $dateFilter);
                    } else if ($startDate && $endDate) {
                        $builder = $builder->where('date >=', $startDate)->where('date <=', $endDate);
                    }
                    
                    // Filtrar por sala
                    if ($roomFilter != 'all') {
                        $builder = $builder->where('room', $roomFilter);
                    }
                    
                    // Filtrar por tipo de juego
                    if ($gameFilter != 'all') {
                        $builder = $builder->where('type', $gameFilter);
                    }
                    
                    $games = $builder->findAll();
                    $filteredGames = [];
                    
                    // Inicializar contadores de estados
                    $gamesByStatus = [
                        'unstarted' => 0,
                        'initiated' => 0,
                        'finished' => 0,
                        'earring' => 0
                    ];
                    
                    // Inicializar contadores de tipos (basados en los juegos filtrados)
                    $gamesByType = [
                        'automatic' => 0,
                        'manual' => 0,
                        'live' => 0,
                        'video' => 0
                    ];
                    
                    // Inicializar array para juegos por sala (basados en los juegos filtrados)
                    $gamesByRoom = [];
                    
                    foreach ($games as &$game) {
                        $room = $modelGameRooms->where('id', $game['room'])->first();
                        $game['room_name'] = $room['name'] ?? translate('unknown');
                        $game['cartons'] = $modelCartons->where('game', $game['id'])->where('user !=', 0)->countAllResults();
                        $game['numbers'] = $modelBoards->where('game', $game['id'])->select('number')->distinct()->countAllResults();
                        $game['players'] = $modelCartons->where('game', $game['id'])->where('user !=', 0)->select('user')->distinct()->countAllResults();
                        $SingsCount = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();
                        $AwardsCount = $modelAwards->where('game', $game['id'])->where('status', 1)->countAllResults();

                        // CORRECCIÓN: Obtener TODOS los premios del juego, no solo el primero
                        $awards = $modelAwards->where('game', $game['id'])->where('status', 1)->findAll();
                        $accumulated = $game['cartons'] * $game['price'];
                        $total_award = $accumulated - ($accumulated * systemGet('rateEarnings'));

                        // Calcular el total sumando todos los premios
                        $game['total'] = 0;
                        
                        if (!empty($awards)) {
                            foreach ($awards as $award) {
                                if ($game['award'] == 2) {
                                    // Si es monto fijo, sumar directamente
                                    $game['total'] += $award['amount'];
                                } else {
                                    // Si es porcentaje, calcular el porcentaje del total disponible
                                    $game['total'] += ($total_award * $award['amount'] / 100);
                                }
                            }
                        }

                        $game['accumulated'] = $accumulated;

                        $game['earnings'] = $accumulated - $game['total'];
                        
                        // Determinar el estado del juego
                        if ($game['numbers'] == 0) {
                            $game['status_value'] = 'unstarted';
                            $game['status'] = '<span class="badge bg-info">' . translate('UNSTARTED') . '</span>';
                            $gamesByStatus['unstarted']++;
                        } elseif ($game['numbers'] >= 75 || $SingsCount >= $AwardsCount) {
                            $game['status_value'] = 'finished';
                            $game['status'] = '<span class="badge bg-success">' . translate('FINISHED') . '</span>';
                            $gamesByStatus['finished']++;
                        } elseif ($game['numbers'] > 0 && $game['numbers'] < 75) {
                            $game['status_value'] = 'initiated';
                            $game['status'] = '<span class="badge bg-primary">' . translate('INITIATED') . '</span>';
                            $gamesByStatus['initiated']++;
                        } else {
                            $game['status_value'] = 'earring';
                            $game['status'] = '<span class="badge bg-warning text-muted">' . translate('EARRING') . '</span>';
                            $gamesByStatus['earring']++;
                        }
                        
                        // Contar por tipo (basado en los juegos filtrados)
                        switch ($game['type']) {
                            case 1:
                                $gamesByType['automatic']++;
                                break;
                            case 2:
                                $gamesByType['manual']++;
                                break;
                            case 3:
                                $gamesByType['live']++;
                                break;
                            case 4:
                                $gamesByType['video']++;
                                break;
                        }
                        
                        // Contar por sala (basado en los juegos filtrados)
                        $roomName = $game['room_name'];
                        if (!isset($gamesByRoom[$roomName])) {
                            $gamesByRoom[$roomName] = 0;
                        }
                        $gamesByRoom[$roomName]++;
                        
                        // Filtrar por estado
                        if ($statusFilter === 'all' || $game['status_value'] === $statusFilter) {
                            // Filtrar por tipo de premio
                            if ($awardFilter === 'all') {
                                $filteredGames[] = $game;
                            } else {
                                $hasAward = $modelAwards->where('game', $game['id'])->where('modality', $awardFilter)->countAllResults() > 0;
                                if ($hasAward) {
                                    $filteredGames[] = $game;
                                }
                            }
                        }
                    }
                    
                    $data['games'] = $filteredGames;
                    
                    // Convertir el array de juegos por sala al formato esperado
                    $data['games_by_room'] = [];
                    foreach ($gamesByRoom as $roomName => $count) {
                        if ($count > 0) {
                            $data['games_by_room'][] = [
                                'name' => $roomName,
                                'count' => $count
                            ];
                        }
                    }
                    
                    // Asignar los contadores calculados basados en los filtros aplicados
                    $data['games_by_type'] = $gamesByType;
                    $data['games_by_status'] = $gamesByStatus;
                    
                    // Estadísticas adicionales para el resumen
                    $data['total_games_filtered'] = count($games);
                    $data['total_cartons_filtered'] = array_sum(array_column($games, 'cartons'));
                    $data['total_players_filtered'] = array_sum(array_column($games, 'players'));
                    $data['total_awards_filtered'] = array_sum(array_column($filteredGames, 'total'));
                }
                break;
                
            case 'users':
                if ($activeTab == 'users' || $activeTab == 'summary') {
                    $builderUsers = $modelUsers;
                    
                    // Aplicar filtros de fecha para usuarios
                    if ($dateFilter != 'all') {
                        switch ($dateFilter) {
                            case 'today':
                                $builderUsers->where('DATE(created_at)', date('Y-m-d'));
                                break;
                            case 'week':
                                $builderUsers->where('created_at >=', date('Y-m-d', strtotime('-1 week')));
                                break;
                            case 'month':
                                $builderUsers->where('created_at >=', date('Y-m-d', strtotime('-1 month')));
                                break;
                            case 'year':
                                $builderUsers->where('created_at >=', date('Y-m-d', strtotime('-1 year')));
                                break;
                        }
                    } elseif ($startDate && $endDate) {
                        $builderUsers->where('created_at >=', $startDate . ' 00:00:00')->where('created_at <=', $endDate . ' 23:59:59');
                    }
                    
                    // CAMBIO IMPORTANTE: Usar $builderUsers en lugar de $modelUsers
                    $users = $builderUsers->where('status', 1)->findAll();
                    
                    // Calcular estadísticas de usuarios
                    $data['users'] = $users;
                    $data['total_users_tab'] = count($users);
                    $data['total_wallet_tab'] = array_sum(array_column($users, 'wallet'));
                    
                    // Usuarios con más saldo
                    $usersByWallet = $users;
                    usort($usersByWallet, function($a, $b) {
                        return $b['wallet'] - $a['wallet'];
                    });
                    $data['top_users_by_wallet'] = array_slice($usersByWallet, 0, 10);
                    
                    // Usuarios más activos (con más cartones)
                    $activeUsers = [];
                    foreach ($users as $user) {
                        $cartons = $modelCartons->where('user', $user['id'])->countAllResults();
                        $activeUsers[] = [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'firstname' => $user['firstname'],
                            'lastname' => $user['lastname'],
                            'phone' => $user['phone'],
                            'email' => $user['email'],
                            'cartons' => $cartons
                        ];
                    }
                    
                    usort($activeUsers, function($a, $b) {
                        return $b['cartons'] - $a['cartons'];
                    });
                    
                    $data['top_active_users'] = array_slice($activeUsers, 0, 10);
                    
                    // Usuarios por grupo (sin filtros de fecha, total general)
                    // Crear nuevas instancias para evitar conflictos con los filtros anteriores
                    $modelUsersGroup = new UsersModel();
                    $data['users_by_group'] = [
                        'admin' => $modelUsersGroup->where('group', 1)->where('status', 1)->countAllResults(),
                        'player' => $modelUsersGroup->where('group', 0)->where('status', 1)->countAllResults()
                    ];
                    
                    // Debug para verificar datos
                    $data['debug_users'] = [
                        'total_users_found' => count($users),
                        'dateFilter' => $dateFilter,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'activeTab' => $activeTab,
                        'first_user' => $users[0] ?? translate('no users found')
                    ];
                }
                break;

            case 'players':
                if ($activeTab == 'players' || $activeTab == 'summary') {
                    // Parámetros de paginación y filtros
                    $page = (int)($this->request->getGet('page') ?? 1);
                    $perPage = 10;
                    $search = $this->request->getGet('search') ?? '';
                    $status = $this->request->getGet('status') ?? 'all';
                    $group = $this->request->getGet('group') ?? 'all';
                    
                    // Usar el modelo directamente (sin select)
                    $builder = $modelUsers->builder();
                    
                    // Filtros
                    if (!empty($search)) {
                        $builder->groupStart()->like('firstname', $search)->orLike('lastname', $search)->orLike('username', $search)->orLike('email', $search)->orLike('phone', $search)->orLike('document', $search)->groupEnd();
                    }
        
                    if ($status !== 'all') {
                        $builder->where('status', $status);
                    }
                    
                    if ($group !== 'all') {
                        $builder->where('group', $group);
                    }
                    
                    // Excluir eliminados
                    $builder->where('deleted', 0);
                    
                    // Obtener el total ANTES de aplicar limit
                    $totalRecords = $builder->countAllResults(false); // false para no resetear
                    
                    // Aplicar paginación
                    $offset = ($page - 1) * $perPage;
                    $users = $builder->limit($perPage, $offset)->get()->getResultArray();
                    
                    // Crear pager manualmente
                    $pager = \Config\Services::pager();
                    $pager->store('default', $page, $perPage, $totalRecords);
                    
                    // Enriquecer datos de usuarios con estadísticas
                    foreach ($users as &$user) {
                        $userId = $user['id'];
                        
                        // Estadísticas del usuario
                        $user['total_cartons'] = $modelCartons->where('user', $userId)->countAllResults();
                        $user['total_deposits'] = $modelDeposits->where('user', $userId)->where('status', 1)->selectSum('amount')->get()->getRow()->amount ?? 0;
                        $user['total_retires'] = $modelRetires->where('user', $userId)->where('status', 1)->selectSum('amount')->get()->getRow()->amount ?? 0;
                        $user['total_roulettes'] = $modelRoulettes->where('user', $userId)->where('status', 1)->selectSum('amount')->get()->getRow()->amount ?? 0;
                        $user['last_activity'] = $this->getLastActivity($userId);
                    }
                    
                    // Estadísticas generales
                    $data['users'] = $users;
                    $data['pager'] = $pager;
                    $data['search'] = $search;
                    $data['status'] = $status;
                    $data['group'] = $group;
                    $data['current_page'] = $page;
                    $data['per_page'] = $perPage;
                    $data['stats'] = $this->getUsersStats();
                }
                break;
                
            case 'deposits':
            case 'retires':
            case 'transactions':
                if ($activeTab == 'transactions' || $activeTab == 'summary') {
                    // Inicializar variables por defecto
                    $data['deposits'] = [];
                    $data['retires'] = [];
                    $data['total_deposits'] = 0;
                    $data['count_deposits'] = 0;
                    $data['total_retires'] = 0;
                    $data['count_retires'] = 0;
                    $data['deposits_by_method'] = [];
                    $data['deposits_by_bank'] = [];
                    $data['retires_by_bank'] = [];
                    
                    // Procesar DEPÓSITOS
                    if ($activeTab == 'deposits' || $activeTab == 'transactions') {
                        $builderDeposits = $modelDeposits;
                        
                        // Aplicar filtros de fecha
                        if ($dateFilter != 'all') {
                            switch ($dateFilter) {
                                case 'today':
                                    $builderDeposits->where('DATE(date)', date('Y-m-d'));
                                    break;
                                case 'week':
                                    $builderDeposits->where('date >=', date('Y-m-d', strtotime('-1 week')));
                                    break;
                                case 'month':
                                    $builderDeposits->where('date >=', date('Y-m-d', strtotime('-1 month')));
                                    break;
                                case 'year':
                                    $builderDeposits->where('date >=', date('Y-m-d', strtotime('-1 year')));
                                    break;
                            }
                        } elseif ($startDate && $endDate) {
                            $builderDeposits->where('date >=', $startDate)->where('date <=', $endDate);
                        }
                        
                        $deposits = $builderDeposits->where('status', 1)->findAll();

                        foreach ($deposits as &$deposit) {
                            $user = $modelUsers->find($deposit['user']);
                            if ($user) {
                                $deposit['username'] = $user['username'];
                                $deposit['firstname'] = $user['firstname'];
                                $deposit['lastname'] = $user['lastname'];
                                $deposit['email'] = $user['email'];
                            } else {
                                $deposit['username'] = translate('unknown');
                                $deposit['firstname'] = translate('unknown');
                                $deposit['lastname'] = translate('user');
                                $deposit['email'] = 'N/A';
                            }
                        }
                        
                        $data['deposits'] = $deposits;
                        $data['total_deposits'] = array_sum(array_column($deposits, 'amount'));
                        $data['count_deposits'] = count($deposits);
                        
                        // Depósitos por método
                        $depositsByMethod = [];
                        foreach ($deposits as $deposit) {
                            $method = $deposit['method'] ?? translate('unknown');
                            if (!isset($depositsByMethod[$method])) {
                                $depositsByMethod[$method] = 0;
                            }
                            $depositsByMethod[$method] += $deposit['amount'];
                        }
                        $data['deposits_by_method'] = $depositsByMethod;
                        
                        // Depósitos por banco
                        $depositsByBank = [];
                        foreach ($deposits as $deposit) {
                            $bank = $deposit['bank'] ?? 'Unknown';
                            if (!isset($depositsByBank[$bank])) {
                                $depositsByBank[$bank] = 0;
                            }
                            $depositsByBank[$bank] += $deposit['amount'];
                        }
                        $data['deposits_by_bank'] = $depositsByBank;
                    }
                    
                    // Procesar RETIROS
                    if ($activeTab == 'retires' || $activeTab == 'transactions') {
                        $builderRetires = $modelRetires;
                        
                        // Aplicar filtros de fecha
                        if ($dateFilter != 'all') {
                            switch ($dateFilter) {
                                case 'today':
                                    $builderRetires->where('DATE(created_at)', date('Y-m-d'));
                                    break;
                                case 'week':
                                    $builderRetires->where('created_at >=', date('Y-m-d', strtotime('-1 week')) . ' 00:00:00');
                                    break;
                                case 'month':
                                    $builderRetires->where('created_at >=', date('Y-m-d', strtotime('-1 month')) . ' 00:00:00');
                                    break;
                                case 'year':
                                    $builderRetires->where('created_at >=', date('Y-m-d', strtotime('-1 year')) . ' 00:00:00');
                                    break;
                            }
                        } elseif ($startDate && $endDate) {
                            $builderRetires->where('created_at >=', $startDate . ' 00:00:00')
                                          ->where('created_at <=', $endDate . ' 23:59:59');
                        }
                        
                        $retires = $builderRetires->where('status', 1)->findAll();

                        foreach ($retires as &$retire) {
                            $user = $modelUsers->find($retire['user']);
                            if ($user) {
                                $retire['username'] = $user['username'];
                                $retire['firstname'] = $user['firstname'];
                                $retire['lastname'] = $user['lastname'];
                                $retire['email'] = $user['email'];
                            } else {
                                $retire['username'] = translate('unknown');
                                $retire['firstname'] = translate('unknown');
                                $retire['lastname'] = translate('user');
                                $retire['email'] = 'N/A';
                            }
                        }
                        
                        $data['retires'] = $retires;
                        $data['total_retires'] = array_sum(array_column($retires, 'amount'));
                        $data['count_retires'] = count($retires);
                        
                        // Retiros por banco
                        $retiresByBank = [];
                        foreach ($retires as $retire) {
                            $bank = $retire['bank'] ?? 'Unknown';
                            if (!isset($retiresByBank[$bank])) {
                                $retiresByBank[$bank] = 0;
                            }
                            $retiresByBank[$bank] += $retire['amount'];
                        }
                        $data['retires_by_bank'] = $retiresByBank;
                    }
                }
                break;

            case 'roulette':
                if ($activeTab == 'roulette' || $activeTab == 'summary') {
                    $builder = $modelRoulettes;
                    
                    // Filtrar por fecha
                    if ($startDate && $endDate) {
                        $builder = $builder->where('created_at >=', $startDate . ' 00:00:00')
                                          ->where('created_at <=', $endDate . ' 23:59:59');
                    }
                    
                    $roulettes = $builder->where('status', 1)->findAll();
                    
                    // Enriquecer datos con información del usuario
                    foreach ($roulettes as &$roulette) {
                        $user = $modelUsers->find($roulette['user']);
                        if ($user) {
                            $roulette['username'] = $user['username'];
                            $roulette['firstname'] = $user['firstname'];
                            $roulette['lastname'] = $user['lastname'];
                            $roulette['email'] = $user['email'];
                        } else {
                            $roulette['username'] = translate('unknown');
                            $roulette['firstname'] = translate('unknown');
                            $roulette['lastname'] = translate('user');
                            $roulette['email'] = 'N/A';
                        }
                    }
                    
                    $data['roulettes'] = $roulettes;
                    $data['total_roulette'] = array_sum(array_column($roulettes, 'amount'));
                    $data['count_roulette'] = count($roulettes);
                    $data['total_cartons_roulette'] = array_sum(array_column($roulettes, 'cartons'));
                    
                    // Promedio de precio por cartón
                    $totalCartons = array_sum(array_column($roulettes, 'cartons'));
                    $data['avg_price_per_carton'] = $totalCartons > 0 ? $data['total_roulette'] / $totalCartons : 0;
                    
                    // Top usuarios de ruleta
                    $rouletteByUser = [];
                    foreach ($roulettes as $roulette) {
                        $userId = $roulette['user'];
                        if (!isset($rouletteByUser[$userId])) {
                            $rouletteByUser[$userId] = [
                                'amount' => 0,
                                'cartons' => 0,
                                'games' => 0,
                                'username' => $roulette['username'],
                                'firstname' => $roulette['firstname'],
                                'lastname' => $roulette['lastname']
                            ];
                        }
                        $rouletteByUser[$userId]['amount'] += $roulette['amount'];
                        $rouletteByUser[$userId]['cartons'] += $roulette['cartons'];
                        $rouletteByUser[$userId]['games']++;
                    }
                    
                    // Ordenar por cantidad
                    uasort($rouletteByUser, function($a, $b) {
                        return $b['amount'] <=> $a['amount'];
                    });
                    
                    $data['roulette_by_user'] = array_slice($rouletteByUser, 0, 10, true);
                    
                    // Estadísticas por día
                    $rouletteByDate = [];
                    foreach ($roulettes as $roulette) {
                        $date = date('Y-m-d', strtotime($roulette['created_at']));
                        if (!isset($rouletteByDate[$date])) {
                            $rouletteByDate[$date] = [
                                'amount' => 0,
                                'cartons' => 0,
                                'games' => 0
                            ];
                        }
                        $rouletteByDate[$date]['amount'] += $roulette['amount'];
                        $rouletteByDate[$date]['cartons'] += $roulette['cartons'];
                        $rouletteByDate[$date]['games']++;
                    }
                    
                    ksort($rouletteByDate);
                    $data['roulette_by_date'] = $rouletteByDate;
                }
                break;
                
            case 'referrals':
                if ($activeTab == 'referrals' || $activeTab == 'summary') {
                    $builder = $modelReferrals;
                    
                    // Filtrar por fecha
                    if ($startDate && $endDate) {
                        $builder = $builder->where('created_at >=', $startDate . ' 00:00:00')->where('created_at <=', $endDate . ' 23:59:59');
                    }
                    
                    $referrals = $builder->where('status', 1)->findAll();
                    
                    $data['referrals'] = $referrals;
                    $data['total_referrals'] = array_sum(array_column($referrals, 'amount'));
                    $data['count_referrals'] = count($referrals);
                    
                    // Top referidores
                    $referralsByReferrer = [];
                    foreach ($referrals as $referral) {
                        if (!isset($referralsByReferrer[$referral['id_referrer']])) {
                            $referralsByReferrer[$referral['id_referrer']] = [
                                'count' => 0,
                                'amount' => 0
                            ];
                        }
                        $referralsByReferrer[$referral['id_referrer']]['count']++;
                        $referralsByReferrer[$referral['id_referrer']]['amount'] += $referral['amount'];
                    }
                    
                    // Obtener información de usuarios
                    foreach ($referralsByReferrer as $userId => $stats) {
                        $user = $modelUsers->find($userId);
                        if ($user) {
                            $referralsByReferrer[$userId]['username'] = $user['username'];
                            $referralsByReferrer[$userId]['firstname'] = $user['firstname'];
                            $referralsByReferrer[$userId]['lastname'] = $user['lastname'];
                        } else {
                            $referralsByReferrer[$userId]['username'] = 'Unknown';
                            $referralsByReferrer[$userId]['firstname'] = 'Unknown';
                            $referralsByReferrer[$userId]['lastname'] = 'User';
                        }
                    }
                    
                    // Ordenar por cantidad de referidos
                    uasort($referralsByReferrer, function($a, $b) {
                        return $b['count'] - $a['count'];
                    });
                    
                    $data['referrals_by_referrer'] = array_slice($referralsByReferrer, 0, 10, true);
                }
                break;
        }
        
        // Seleccionar la vista según la pestaña activa
        switch ($activeTab) {
            case 'summary':
                return view('games/statistics/summary', $data);
            case 'games':
                return view('games/statistics/games', $data);
            case 'users':
                return view('games/statistics/users', $data);
            case 'transactions':
                return view('games/statistics/transactions', $data);
            case 'roulette':
                return view('games/statistics/roulette', $data);
            case 'referrals':
                return view('games/statistics/referrals', $data);
            case 'players':
                return view('games/statistics/players', $data);
            default:
                return view('games/statistics/summary', $data);
        }
    }

    private function getLastActivity($userId) {
        $modelCartons = new CartonsModel();
        $modelDeposits = new DepositsModel();
        $modelRetires = new RetiresModel();

        $lastCarton = $modelCartons->where('user', $userId)->orderBy('created_at', 'DESC')->first();
        $lastDeposit = $modelDeposits->where('user', $userId)->orderBy('created_at', 'DESC')->first();
        $lastRetire = $modelRetires->where('user', $userId)->orderBy('created_at', 'DESC')->first();
        
        $dates = [];
        if ($lastCarton) $dates[] = $lastCarton['created_at'];
        if ($lastDeposit) $dates[] = $lastDeposit['created_at'];
        if ($lastRetire) $dates[] = $lastRetire['created_at'];
        
        return !empty($dates) ? max($dates) : null;
    }

    private function getUsersStats() {
        $modelUsers = new UsersModel();

        $stats = [];
        
        // Total de usuarios
        $stats['total_users'] = $modelUsers->where('deleted', 0)->countAllResults();
        
        // Usuarios activos
        $stats['active_users'] = $modelUsers->where('status', 1)->where('deleted', 0)->countAllResults();
        
        // Usuarios baneados
        $stats['banned_users'] = $modelUsers->where('status', 0)->where('deleted', 0)->countAllResults();
        
        // Usuarios por grupo
        $stats['admin_users'] = $modelUsers->where('group', 1)->where('deleted', 0)->countAllResults();
        $stats['player_users'] = $modelUsers->where('group', 0)->where('deleted', 0)->countAllResults();
        
        // Total en wallets
        $stats['total_wallet'] = $modelUsers->where('deleted', 0)->selectSum('wallet')->get()->getRow()->wallet ?? 0;
        
        // Promedio por usuario
        $stats['avg_wallet'] = $stats['total_users'] > 0 ? $stats['total_wallet'] / $stats['total_users'] : 0;
        
        // Usuarios registrados hoy
        $stats['today_users'] = $modelUsers->where('DATE(created_at)', date('Y-m-d'))->where('deleted', 0)->countAllResults();
        
        // Usuarios registrados esta semana
        $stats['week_users'] = $modelUsers->where('created_at >=', date('Y-m-d', strtotime('-7 days')))->where('deleted', 0)->countAllResults();
        
        // Usuarios registrados este mes
        $stats['month_users'] = $modelUsers->where('created_at >=', date('Y-m-d', strtotime('-30 days')))->where('deleted', 0)->countAllResults();
        
        return $stats;
    }
    
    public function start() {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }
        
        $modelUsers = new UsersModel();
        $modelGames = new GamesModel();
        $modelModalities = new ModalitiesModel();
        $modelSings = new SingsModel();
        $modelAwards = new AwardsModel();
        $modelBoards = new BoardsModel();
        $modelContacts = new ContactsModel();

        $contacts = $modelContacts->findAll();

        $modalities = $modelModalities->where('status', 1)->findAll();

        $user = $modelUsers->find(session()->get('id'));

        $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');

        $data = [
            'page' => [
                'title' => translate('start game')
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('games/start', ['contacts' => $contacts, 'user' => $user, 'modalities' => $modalities, 'imagePath' => $imagePath]) 
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }

    public function game($game_id) {
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();

        $game = $modelGames->find($game_id);

        if (session()->get('group') == 1) {
            if (!$game) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => translate('game not found'),
                    'redirect' => site_url('/games'),
                ]);
            }

            if (bingo_count_drawn_numbers((int) $game['id']) === 0 && !bingo_can_start_game($game)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => bingo_min_players_start_message($game),
                ]);
            }

            session()->set('game_id', $game_id);

            return $this->response->setJSON([
                'success' => true,
                'redirect' => site_url('/board'),
            ]);
        }

        if (session()->get('group') == 0) {
            if (!$game) {
                return $this->response->setJSON([
                    'success' => true,
                    'redirect' => site_url('/play'),
                ]);
            }

            $cartons = $modelCartons->getCartonsByUser(session()->get('id'), $game['id']);

            if (empty($cartons)) {
                return $this->response->setJSON([
                    'success' => true,
                    'redirect' => site_url('/play'),
                ]);
            }

            session()->set('game_id', $game_id);

            return $this->response->setJSON([
                'success' => true,
                'redirect' => site_url('/playing'),
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => translate('unauthorized access'),
        ]);
    }

    public function live($game_id) {
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();

        $game = $modelGames->find($game_id);

        if (!$game) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('game not found'),
                'redirect' => site_url('/games'),
            ]);
        }

        if (session()->get('group') == 1
            && bingo_count_drawn_numbers((int) $game['id']) === 0
            && !bingo_can_start_game($game)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => bingo_min_players_start_message($game),
            ]);
        }

        session()->set('game_id', $game_id);

        return $this->response->setJSON([
            'success' => true,
            'redirect' => site_url('/live'),
        ]);
    }

    public function uploadVideo() {
        $response = ['success' => false, 'message' => '', 'filename' => ''];
        
        try {
            $videoFile = $this->request->getFile('video');
            
            if (!$videoFile || !$videoFile->isValid()) {
                $response['message'] = translate('no video file received');
                return $this->response->setJSON($response);
            }
            
            // Validar tamaño (50MB máximo)
            $maxSize = 50 * 1024 * 1024; // 50MB en bytes
            if ($videoFile->getSize() > $maxSize) {
                $response['message'] = translate('video file is too large. maximum size: 50MB');
                return $this->response->setJSON($response);
            }
            
            // Validar extensión
            $allowedExtensions = ['mp4', 'avi', 'mov', 'wmv'];
            $extension = $videoFile->getClientExtension();
            
            if (!in_array(strtolower($extension), $allowedExtensions)) {
                $response['message'] = translate('invalid video format');
                return $this->response->setJSON($response);
            }
            
            // Crear directorio si no existe
            $uploadPath = FCPATH . 'uploads/videos/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // Generar nombre único
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            
            // Mover archivo
            if ($videoFile->move($uploadPath, $fileName)) {
                $response['success'] = true;
                $response['filename'] = $fileName;
                $response['message'] = translate('video uploaded successfully');
            } else {
                $response['message'] = translate('error moving video file');
            }
            
        } catch (Exception $e) {
            $response['message'] = translate('error uploading video') . ': ' . $e->getMessage();
        }
        
        return $this->response->setJSON($response);
    }

    public function addgameSubmit() {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        $model = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $awardModel = new AwardsModel(); 

        $type = $this->request->getPost('type');

        $validationRules = [
            'room' => [
                'label' => translate('game room'), 
                'rules' => 'required'
            ],
            'description' => [
                'label' => translate('description'), 
                'rules' => 'required|min_length[3]'
            ],
            'price' => [
                'label' => translate('price') . ' ' . translate('of the') . ' ' . translate('carton'),
                'rules' => 'required'
            ],
            'min_players' => [
                'label' => translate('minimum players to start'),
                'rules' => 'required|integer|greater_than[0]|less_than_equal_to[9999]'
            ],
            'date' => [
                'label' => translate('date'), 
                'rules' => 'required|valid_date[Y-m-d]'
            ],
            'time' => [
                'label' => translate('time'), 
                'rules' => 'required|regex_match[/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/]'
            ],
            'award' => [
                'label' => translate('type of award'),
                'rules' => 'required'
            ],
            'type' => [
                'label' => translate('type of game'),
                'rules' => 'required'
            ],
            'reset' => [
                'label' => translate('reset in each modality'),
                'rules' => 'required'
            ]
        ];

        $videoFileName = '';

        $url = '';

        if ($type == '3') {
            $validationRules['url'] = [
                'label' => translate('url'),
                'rules' => 'required|valid_url'
            ];

            $inputUrl = $this->request->getPost('url');

            // --- Extraer el ID del video ---
            $videoId = '';

            // Caso 1: URL normal con watch?v=
            if (preg_match('/v=([a-zA-Z0-9_-]+)/', $inputUrl, $matches)) {
                $videoId = $matches[1];
            }

            // Caso 2: URL corta youtu.be/xxxx
            elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $inputUrl, $matches)) {
                $videoId = $matches[1];
            }

            // Caso 3: Si ya viene como embed
            elseif (preg_match('/embed\/([a-zA-Z0-9_-]+)/', $inputUrl, $matches)) {
                $videoId = $matches[1];
            }

            if ($videoId) {
                $url = "https://www.youtube.com/embed/{$videoId}?autoplay=1&mute=1&modestbranding=1&rel=0";
            } else {
                // Si no se puede extraer, dejar el original
                $url = $inputUrl;
            }
        }

        if ($type == '4') { 
            $uploadedVideoName = $this->request->getPost('uploaded_video_name');
        
            if (empty($uploadedVideoName)) {
                $validationRules['video'] = [
                    'label' => translate('video'),
                    'rules' => 'required'
                ];
            } else {
                $videoPath = FCPATH . 'uploads/videos/' . $uploadedVideoName;
                if (!file_exists($videoPath)) {
                    $errors['video'] = translate('uploaded video file not found');
                    return $this->response->setJSON(['success' => false, 'errors' => $errors]);
                }
                
                $videoFileName = $uploadedVideoName;
            }
        }

        if (!$this->validate($validationRules)) {
            $errors = $this->validator->getErrors();
            $response = [
                'success' => false,
                'errors' => $errors 
            ];
            return $this->response->setJSON($response);
        }

        $modalities    = $this->request->getPost('modality');      
        $observations  = $this->request->getPost('observation');   
        $amounts       = $this->request->getPost('amount'); 
        $awardIds      = $this->request->getPost('award_id');
        $awardsDeleted = $this->request->getPost('awards_deleted');

        if (empty($modalities) || !is_array($modalities) || count($modalities) === 0) {
            return $this->response->setJSON([
                'success' => false,
                'errors'  => ['modalities' => translate('you must add at least one modality')]
            ]);
        }

        if (count($modalities) !== count($observations) || count($modalities) !== count($amounts)) {
            return $this->response->setJSON([
                'success' => false,
                'errors'  => ['global' => translate('the data on modalities, amounts and observations do not coincide')]
            ]);
        }

        $md = implode(',', $modalities);

        $gameData = [
            'user' => session()->get('id'),
            'room' => $this->request->getPost('room'),
            'description' => $this->request->getPost('description'),
            'price' => $this->request->getPost('price'),
            'min_players' => max(1, (int) $this->request->getPost('min_players')),
            'modalities' => $md,
            'date' => $this->request->getPost('date'),
            'time' => $this->request->getPost('time'),
            'award' => $this->request->getPost('award'),
            'reset' => $this->request->getPost('reset'),
            'type' => $type,
            'url' => $url
        ];

        if (!empty($videoFileName)) {
            $gameData['video'] = $videoFileName;
        }

        $coverImage = $this->request->getPost('cover');
        if ($coverImage) {
            // Si llega base64 => nueva imagen
            if (strpos($coverImage, 'data:image') === 0) {
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $coverImage));
                $fileName  = uniqid() . '.png';  
                $uploadPath = FCPATH . 'uploads/covers/';
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true); 
                }
                file_put_contents($uploadPath . $fileName, $imageData);
                $gameData['cover'] = $fileName;
            }
        } else {
            // Si se eliminó la imagen → dejar null
            $gameData['cover'] = null;
        }

        $action = $this->request->getPost('game-action');
        $gameId = $this->request->getPost('game-id');

        if ($action === 'update' && $gameId) {
            $model->update($gameId, $gameData);

            if (!empty($awardsDeleted) && is_array($awardsDeleted)) {
                $awardModel->whereIn('id', $awardsDeleted)->delete();
            }

            $finalAwards = [];
            foreach ($modalities as $i => $modalityId) {
                $finalAwards[] = [
                    'award_id'    => is_array($awardIds ?? null) && array_key_exists($i, $awardIds) ? $awardIds[$i] : null,
                    'modality'    => $modalityId,
                    'amount'      => $amounts[$i],
                    'observation' => $observations[$i]
                ];
            }

            foreach ($finalAwards as $aw) {
                if (!empty($aw['award_id'])) {
                    $awardModel->update($aw['award_id'], [
                        'modality'    => $aw['modality'],
                        'amount'      => $aw['amount'],
                        'observation' => $aw['observation'],
                        'status'      => 1
                    ]);
                } else {
                    $awardModel->insert([
                        'game'        => $gameId,
                        'modality'    => $aw['modality'],
                        'amount'      => $aw['amount'],
                        'observation' => $aw['observation'],
                        'status'      => 1
                    ]);
                }
            }
        } else {
            $model->insert($gameData);
            $gameId = $model->getInsertID();

            foreach ($modalities as $index => $modalityId) {
                $awardData = [
                    'game' => $gameId,          
                    'modality' => $modalityId,  
                    'observation' => $observations[$index], 
                    'amount' => $amounts[$index],  
                    'status' => 1
                ];

                $awardModel->insert($awardData);
            }

            /*if (systemGet('generateCartons') >= 1) {
                $cartonData = [];
                        
                for ($i = 0; $i < systemGet('generateCartons'); $i++) {
                    $cartonData[] = [
                        'user' => 0,
                        'game' => $gameId,
                        'status' => 1
                    ];
                }

                $modelCartons->insertBatch($cartonData);
                $cartonIds = $modelCartons->select('id')->where('game', $gameId)->findColumn('id');
            
                $numbersData = [];
                foreach ($cartonIds as $cartonId) {

                    $prefix = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

                    $cartonFormatted = str_pad($cartonId, 6, '0', STR_PAD_LEFT);

                    $serial = $cartonFormatted . $prefix;

                    $modelCartons->update($cartonId, ['serial' => $serial]);

                    $bColumn = range(1, 15);  
                    $iColumn = range(16, 30);
                    $nColumn = range(31, 45); 
                    $gColumn = range(46, 60); 
                    $oColumn = range(61, 75); 
            
                    shuffle($bColumn);
                    shuffle($iColumn);
                    shuffle($nColumn);
                    shuffle($gColumn);
                    shuffle($oColumn);
            
                    for ($pos = 0; $pos < 5; $pos++) {
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => $bColumn[$pos],
                            'position' => 1 + ($pos * 5),
                            'status' => 0
                        ];
                    }
            
                    for ($pos = 0; $pos < 5; $pos++) {
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => $iColumn[$pos],
                            'position' => 2 + ($pos * 5),
                            'status' => 0
                        ];
                    }
            
                    for ($pos = 0; $pos < 5; $pos++) {
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => $nColumn[$pos],
                            'position' => 3 + ($pos * 5),
                            'status' => 0
                        ];
                    }

                    for ($pos = 0; $pos < 5; $pos++) {
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => $gColumn[$pos],
                            'position' => 4 + ($pos * 5),
                            'status' => 0
                        ];
                    }
            
                    for ($pos = 0; $pos < 5; $pos++) {
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => $oColumn[$pos],
                            'position' => 5 + ($pos * 5),
                            'status' => 0
                        ];
                    }
                }
            
                $modelNumbersCartons->insertBatch($numbersData);
            }*/

            $modelUsers = new UsersModel();
            $modelAwards = new AwardsModel();
            $modelNotifications = new NotificationsModel();

            //$pushService = new PushNotificationService();
            $pushService = new SimplePushService();

            $currentUserId = session()->get('id');
            $users = $modelUsers->where('id !=', $currentUserId)->findAll();
            $total = $modelAwards->where('game', $gameId)->selectSum('amount')->get()->getRow()->amount ?? 0;

            foreach ($users as $user) {

                $awardText = $gameData['award'] == 2 ? systemGet('currency') . ' ' . number_format($total, 2) : translate('accumulated');

                $notificationData = [
                    'user' => $user['id'],
                    'from' => $currentUserId,
                    'game' => $gameId,
                    'modality' => $gameData['modalities'],
                    'title' => '✅ NUEVA PARTIDA AGREGADA',
                    'message' => $gameData['description'] . ' 🗓️ ' . translate_day($gameData['date'] . ' ' . $gameData['time']) . ', ' . translate_date($gameData['date']) . ' | 🎫 Precio del cartón: ' . systemGet('currency') . ' ' . number_format($gameData['price'], 2) . ' | 🏆 Premio total: ' . $awardText,
                ];

                $modelNotifications->insert($notificationData);

                $pushPayload = [
                    'title' => $notificationData['title'],
                    'message' => $notificationData['message'],
                    'game' => $gameId,
                    'url' => base_url('/game/' . $gameId)
                ];

                $results = $pushService->sendToUser($user['id'], $pushPayload);
                
                // Log de resultados para debugging
                foreach ($results as $result) {
                    if (!$result['success']) {
                        log_message('error', 'Push notification failed: ' . json_encode($result));
                    }
                }
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => ($action === 'update') ? translate('game updated successfully') : translate('game added successfully'),
            'date'    => $gameData['date'],
            'dateText'=> translate_date($gameData['date'])
        ]);
    }

    public function startgameSubmit() {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        $model = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $awardModel = new AwardsModel(); 

        $type = $this->request->getPost('type');

        $validationRules = [
            'room' => [
                'label' => translate('game room'), 
                'rules' => 'required'
            ],
            'description' => [
                'label' => translate('description'), 
                'rules' => 'required|min_length[3]'
            ],
            'price' => [
                'label' => translate('price') . ' ' . translate('of the') . ' ' . translate('carton'),
                'rules' => 'required'
            ],
            'min_players' => [
                'label' => translate('minimum players to start'),
                'rules' => 'required|integer|greater_than[0]|less_than_equal_to[9999]'
            ],
            'date' => [
                'label' => translate('date'), 
                'rules' => 'required|valid_date[Y-m-d]'
            ],
            'time' => [
                'label' => translate('time'), 
                'rules' => 'required|regex_match[/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/]'
            ],
            'award' => [
                'label' => translate('type of award'),
                'rules' => 'required'
            ],
            'type' => [
                'label' => translate('type of game'),
                'rules' => 'required'
            ],
            'reset' => [
                'label' => translate('reset in each modality'),
                'rules' => 'required'
            ]
        ];

        $videoFileName = '';

        $url = '';

        if ($type == '3') {
            $validationRules['url'] = [
                'label' => translate('url'),
                'rules' => 'required|valid_url'
            ];

            $inputUrl = $this->request->getPost('url');

            // --- Extraer el ID del video ---
            $videoId = '';

            // Caso 1: URL normal con watch?v=
            if (preg_match('/v=([a-zA-Z0-9_-]+)/', $inputUrl, $matches)) {
                $videoId = $matches[1];
            }

            // Caso 2: URL corta youtu.be/xxxx
            elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $inputUrl, $matches)) {
                $videoId = $matches[1];
            }

            // Caso 3: Si ya viene como embed
            elseif (preg_match('/embed\/([a-zA-Z0-9_-]+)/', $inputUrl, $matches)) {
                $videoId = $matches[1];
            }

            if ($videoId) {
                $url = "https://www.youtube.com/embed/{$videoId}?autoplay=1&mute=1&modestbranding=1&rel=0";
            } else {
                // Si no se puede extraer, dejar el original
                $url = $inputUrl;
            }
        }

        if ($type == '4') { 
            $uploadedVideoName = $this->request->getPost('uploaded_video_name');
        
            if (empty($uploadedVideoName)) {
                $validationRules['video'] = [
                    'label' => translate('video'),
                    'rules' => 'required'
                ];
            } else {
                $videoPath = FCPATH . 'uploads/videos/' . $uploadedVideoName;
                if (!file_exists($videoPath)) {
                    $errors['video'] = translate('uploaded video file not found');
                    return $this->response->setJSON(['success' => false, 'errors' => $errors]);
                }
                
                $videoFileName = $uploadedVideoName;
            }
        }

        if (!$this->validate($validationRules)) {
            $errors = $this->validator->getErrors();
            $response = [
                'success' => false,
                'errors' => $errors 
            ];
            return $this->response->setJSON($response);
        }

        $modalities    = $this->request->getPost('modality');      
        $observations  = $this->request->getPost('observation');   
        $amounts       = $this->request->getPost('amount'); 
        $awardIds      = $this->request->getPost('award_id');
        $awardsDeleted = $this->request->getPost('awards_deleted');

        if (empty($modalities) || !is_array($modalities) || count($modalities) === 0) {
            return $this->response->setJSON([
                'success' => false,
                'errors'  => ['modalities' => translate('you must add at least one modality')]
            ]);
        }

        if (count($modalities) !== count($observations) || count($modalities) !== count($amounts)) {
            return $this->response->setJSON([
                'success' => false,
                'errors'  => ['global' => translate('the data on modalities, amounts and observations do not coincide')]
            ]);
        }

        $md = implode(',', $modalities);

        $gameData = [
            'user' => session()->get('id'),
            'room' => $this->request->getPost('room'),
            'description' => $this->request->getPost('description'),
            'price' => $this->request->getPost('price'),
            'min_players' => max(1, (int) $this->request->getPost('min_players')),
            'modalities' => $md,
            'date' => $this->request->getPost('date'),
            'time' => $this->request->getPost('time'),
            'award' => $this->request->getPost('award'),
            'reset' => $this->request->getPost('reset'),
            'type' => $type,
            'url' => $url
        ];

        if (!empty($videoFileName)) {
            $gameData['video'] = $videoFileName;
        }

        $coverImage = $this->request->getPost('cover');
        if ($coverImage) {
            // Si llega base64 => nueva imagen
            if (strpos($coverImage, 'data:image') === 0) {
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $coverImage));
                $fileName  = uniqid() . '.png';  
                $uploadPath = FCPATH . 'uploads/covers/';
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true); 
                }
                file_put_contents($uploadPath . $fileName, $imageData);
                $gameData['cover'] = $fileName;
            }
        } else {
            // Si se eliminó la imagen → dejar null
            $gameData['cover'] = null;
        }

        $action = $this->request->getPost('game-action');
        $gameId = $this->request->getPost('game-id');

        if ($action === 'update' && $gameId) {
            $model->update($gameId, $gameData);

            if (!empty($awardsDeleted) && is_array($awardsDeleted)) {
                $awardModel->whereIn('id', $awardsDeleted)->delete();
            }

            $finalAwards = [];
            foreach ($modalities as $i => $modalityId) {
                $finalAwards[] = [
                    'award_id'    => is_array($awardIds ?? null) && array_key_exists($i, $awardIds) ? $awardIds[$i] : null,
                    'modality'    => $modalityId,
                    'amount'      => $amounts[$i],
                    'observation' => $observations[$i]
                ];
            }

            foreach ($finalAwards as $aw) {
                if (!empty($aw['award_id'])) {
                    $awardModel->update($aw['award_id'], [
                        'modality'    => $aw['modality'],
                        'amount'      => $aw['amount'],
                        'observation' => $aw['observation'],
                        'status'      => 1
                    ]);
                } else {
                    $awardModel->insert([
                        'game'        => $gameId,
                        'modality'    => $aw['modality'],
                        'amount'      => $aw['amount'],
                        'observation' => $aw['observation'],
                        'status'      => 1
                    ]);
                }
            }
        } else {
            $model->insert($gameData);
            $gameId = $model->getInsertID();

            foreach ($modalities as $index => $modalityId) {
                $awardData = [
                    'game' => $gameId,          
                    'modality' => $modalityId,  
                    'observation' => $observations[$index], 
                    'amount' => $amounts[$index],  
                    'status' => 1
                ];

                $awardModel->insert($awardData);
            }

            /*if (systemGet('generateCartons') >= 1) {
                $cartonData = [];
                        
                for ($i = 0; $i < systemGet('generateCartons'); $i++) {
                    $cartonData[] = [
                        'user' => 0,
                        'game' => $gameId,
                        'status' => 1
                    ];
                }

                $modelCartons->insertBatch($cartonData);
                $cartonIds = $modelCartons->select('id')->where('game', $gameId)->findColumn('id');
            
                $numbersData = [];
                foreach ($cartonIds as $cartonId) {

                    $prefix = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

                    $cartonFormatted = str_pad($cartonId, 6, '0', STR_PAD_LEFT);

                    $serial = $cartonFormatted . $prefix;

                    $modelCartons->update($cartonId, ['serial' => $serial]);

                    $bColumn = range(1, 15);  
                    $iColumn = range(16, 30);
                    $nColumn = range(31, 45); 
                    $gColumn = range(46, 60); 
                    $oColumn = range(61, 75); 
            
                    shuffle($bColumn);
                    shuffle($iColumn);
                    shuffle($nColumn);
                    shuffle($gColumn);
                    shuffle($oColumn);
            
                    for ($pos = 0; $pos < 5; $pos++) {
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => $bColumn[$pos],
                            'position' => 1 + ($pos * 5),
                            'status' => 0
                        ];
                    }
            
                    for ($pos = 0; $pos < 5; $pos++) {
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => $iColumn[$pos],
                            'position' => 2 + ($pos * 5),
                            'status' => 0
                        ];
                    }
            
                    for ($pos = 0; $pos < 5; $pos++) {
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => $nColumn[$pos],
                            'position' => 3 + ($pos * 5),
                            'status' => 0
                        ];
                    }

                    for ($pos = 0; $pos < 5; $pos++) {
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => $gColumn[$pos],
                            'position' => 4 + ($pos * 5),
                            'status' => 0
                        ];
                    }
            
                    for ($pos = 0; $pos < 5; $pos++) {
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => $oColumn[$pos],
                            'position' => 5 + ($pos * 5),
                            'status' => 0
                        ];
                    }
                }
            
                $modelNumbersCartons->insertBatch($numbersData);
            }*/

            $modelUsers = new UsersModel();
            $modelAwards = new AwardsModel();
            $modelNotifications = new NotificationsModel();

            //$pushService = new PushNotificationService();
            $pushService = new SimplePushService();

            $currentUserId = session()->get('id');
            $users = $modelUsers->where('id !=', $currentUserId)->findAll();
            $total = $modelAwards->where('game', $gameId)->selectSum('amount')->get()->getRow()->amount ?? 0;

            foreach ($users as $user) {

                $awardText = $gameData['award'] == 2 ? systemGet('currency') . ' ' . number_format($total, 2) : translate('accumulated');

                $notificationData = [
                    'user' => $user['id'],
                    'from' => $currentUserId,
                    'game' => $gameId,
                    'modality' => $gameData['modalities'],
                    'title' => '✅ NUEVA PARTIDA AGREGADA',
                    'message' => $gameData['description'] . ' 🗓️ ' . translate_day($gameData['date'] . ' ' . $gameData['time']) . ', ' . translate_date($gameData['date']) . ' | 🎫 Precio del cartón: ' . systemGet('currency') . ' ' . number_format($gameData['price'], 2) . ' | 🏆 Premio total: ' . $awardText,
                ];

                $modelNotifications->insert($notificationData);

                $pushPayload = [
                    'title' => $notificationData['title'],
                    'message' => $notificationData['message'],
                    'game' => $gameId,
                    'url' => base_url('/game/' . $gameId)
                ];

                $results = $pushService->sendToUser($user['id'], $pushPayload);
                
                // Log de resultados para debugging
                foreach ($results as $result) {
                    if (!$result['success']) {
                        log_message('error', 'Push notification failed: ' . json_encode($result));
                    }
                }
            }
        }

        $game = $model->find($gameId);

        if ($game && bingo_count_drawn_numbers((int) $game['id']) === 0 && !bingo_can_start_game($game)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => bingo_min_players_start_message($game),
            ]);
        }

        session()->set('game_id', $gameId);

        return $this->response->setJSON([
            'success' => true,
            'redirect' => site_url('/board')
        ]);
    }

    public function gamesGet() {
        $modelGames = new GamesModel();
        $modelGameRooms = new GameRoomsModel();

        $data['dates'] = $modelGames->select('date')->distinct()->orderBy('date', 'asc')->findColumn('date');
        $data['rooms'] = $modelGameRooms->where('status', 1)->findAll();
        $data['status'] = [
            'all' => translate('all status'),
            'unstarted' => translate('unstarted'),
            'initiated' => translate('initiated'),
            'finished'  => translate('finished'),
        ];

        return view('games/modal', $data);
    }

    public function gameslistGet($dateFilter = 'all', $roomFilter = 'all', $statusFilter = 'all', $page = 1) {
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelAwards = new AwardsModel();
        $modelBoards = new BoardsModel();
        $modelSings = new SingsModel();
        $modelGameRooms = new GameRoomsModel();

        $per_page = 5; // Número de registros por página
        $page = max(1, (int)$page);

        $builder = $modelGames;

        if (session()->get('group') == 1) {
            $builder = $builder->where('user', session()->get('id'));
        }

        if (!empty($dateFilter) && $dateFilter != 'all') {
            $builder = $builder->where('date', $dateFilter);
        }

        if (!empty($roomFilter) && $roomFilter != 'all') {
            $builder = $builder->where('room', $roomFilter);
        }

        // Obtener todos los juegos para filtrar por estado
        $allGames = $builder->findAll();
        $filteredGames = [];

        foreach ($allGames as &$game) {
            $room = $modelGameRooms->where('id', $game['room'])->where('status', 1)->first();
            
            $game['room'] = $room['name'];
            $game['cartons'] = $modelCartons->where('game', $game['id'])->where('user', session()->get('id'))->countAllResults();
            $game['numbers'] = $modelBoards->where('game', $game['id'])->select('number')->distinct()->countAllResults();
            $game['players'] = $modelCartons->where('game', $game['id'])->where('user !=', 0)->select('user')->distinct()->countAllResults();
            $SingsCount = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();
            $AwardsCount = $modelAwards->where('game', $game['id'])->where('status', 1)->countAllResults();

            $cartons = $modelCartons->where('game', $game['id'])->where('user !=', 0)->countAllResults();
            $accumulated = $cartons * $game['price'];
            $gameAccumulated = $accumulated - ($accumulated * systemGet('rateEarnings'));

            $numbers = $modelBoards->where('game', $game['id'])->countAllResults();
                    
            $percentage = ($numbers / 75) * 100;
        
            $game['numbers_called'] = $numbers;

            $game['percentage'] = round($percentage, 1);

            $game['total'] = $gameAccumulated;
            
            if ($game['numbers'] == 0) {
                $game['status_value'] = 'unstarted';
                $game['status'] = '<span class="badge bg-info">' . translate('UNSTARTED') . '</span>';
            } elseif ($game['numbers'] >= 75 || $SingsCount >= $AwardsCount) {
                $game['status_value'] = 'finished';
                $game['status'] = '<span class="badge bg-success">' . translate('FINISHED') . '</span>';
            } elseif ($game['numbers'] > 0 && $game['numbers'] < 75) {
                $game['status_value'] = 'initiated';
                $game['status'] = '<span class="badge bg-primary">' . translate('INITIATED') . '</span>';
            }

            $buttons = '';
            $canView = ($game['numbers'] == 75 || $SingsCount >= $AwardsCount);
            $canStart = ($game['numbers'] > 0) || bingo_can_start_game($game, (int) $game['players']);
            $minPlayersMessage = esc(bingo_min_players_start_message($game, (int) $game['players']), 'attr');

            if (session()->get('group') == 1) {
                $playButtonClass = $canView ? 'primary' : ($canStart ? 'success' : 'secondary');
                $playButtonIcon = $canView ? 'eye' : 'play';
                $playButtonAction = ($canView || $canStart)
                    ? "gameGet('" . $game['id'] . "');"
                    : "notifyMinPlayersRequired('" . $minPlayersMessage . "');";

                if ($game['type'] != 3) {
                    $buttons = '<div class="btn-group" role="group"><a class="btn btn-' . $playButtonClass . ' btn-modal btn-sm" onclick="' . $playButtonAction . '" style="width: 40px; height: 40px; font-size: 1rem; margin: auto;"><i class="fa-duotone fa-solid fa-' . $playButtonIcon . '"></i></a><button type="button" class="btn btn-modal btn-info btn-sm" onclick="updateGame(\'' . $game['id'] . '\');" style="width: 40px; height: 40px; font-size: 1rem; margin: auto;"><i class="fa-duotone fa-solid fa-pen"></i></button><button type="button" class="btn btn-modal btn-danger btn-sm" onclick="deleteGame(\'' . $game['id'] . '\');" style="width: 40px; height: 40px; font-size: 1rem; margin: auto;"><i class="fa-duotone fa-solid fa-trash"></i></button></div>';
                } else {
                    $liveButtonAction = $canStart ? "liveGet('" . $game['id'] . "');" : "notifyMinPlayersRequired('" . $minPlayersMessage . "');";
                    $buttons = '<div class="btn-group" role="group"><a class="btn btn-' . $playButtonClass . ' btn-modal btn-sm" onclick="' . $playButtonAction . '" style="width: 40px; height: 40px; font-size: 1rem; margin: auto;"><i class="fa-duotone fa-solid fa-' . $playButtonIcon . '"></i></a><a style="width: 40px; height: 40px; font-size: 1rem; margin: auto;" class="btn btn-primary btn-modal text-white" onclick="' . $liveButtonAction . '"><i class="fa-duotone fa-solid fa-desktop"></i></a><button type="button" class="btn btn-modal btn-info btn-sm" onclick="updateGame(\'' . $game['id'] . '\');" style="width: 40px; height: 40px; font-size: 1rem; margin: auto;"><i class="fa-duotone fa-solid fa-pen"></i></button><button type="button" class="btn btn-modal btn-danger btn-sm" onclick="deleteGame(\'' . $game['id'] . '\');" style="width: 40px; height: 40px; font-size: 1rem; margin: auto;"><i class="fa-duotone fa-solid fa-trash"></i></button></div>';
                }
            } else {
                if ($canView) {
                    $buttons = '<a class="btn btn-primary btn-modal text-white" onclick="gameGet(\'' . $game['id'] . '\');"><i class="fa-duotone fa-solid fa-eye"></i></a>';
                } elseif ($game['cartons'] > 0) {
                    $buttons = '<a class="btn btn-success btn-modal text-white" onclick="gameGet(\'' . $game['id'] . '\');"><i class="fa-duotone fa-solid fa-play"></i></a>';
                } else {
                    $buttons = '<a class="btn btn-success btn-modal text-white linkPage" href="' . site_url('play') . '"><i class="fa-duotone fa-solid fa-play"></i></a>';
                }
            }

            $game['buttons'] = $buttons;

            // Filtrar por estado
            if (empty($statusFilter) || $statusFilter === 'all' || $game['status_value'] === $statusFilter) {
                $filteredGames[] = $game;
            }
        }

        // Aplicar paginación a los juegos filtrados
        $totalRecords = count($filteredGames);
        $totalPages = ceil($totalRecords / $per_page);
        $currentPage = min($page, $totalPages); // Asegurar que no exceda el total de páginas
        $offset = ($currentPage - 1) * $per_page;
        $paginatedGames = array_slice($filteredGames, $offset, $per_page);

        $data = [
            'games' => $paginatedGames,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'per_page' => $per_page,
            'dateFilter' => $dateFilter,
            'roomFilter' => $roomFilter,
            'statusFilter' => $statusFilter
        ];

        return view('games/gameslist', $data);
    }

    public function awardsGet($game_id) {
        $modelGames = new GamesModel();
        $modelSings = new SingsModel();
        $modelUsers = new UsersModel(); 
        $modelModalities = new ModalitiesModel();
        $modelAwards = new AwardsModel();
        
        $game = $modelGames->find($game_id);

        $data['game'] = $game;

        $sings = $modelSings->where('game', $game['id'])->findAll();

        foreach ($sings as &$sing) {
            $user = $modelUsers->find($sing['user']);
            $modality = $modelModalities->find($sing['modality']);

            $award = $modelAwards->where('game', $game['id']) ->where('modality', $sing['modality'])  ->first();

            $sing['user_name'] = $user ? $user['name'] : translate('user not found');
            $sing['modality_name'] = $modality ? $modality['name'] : translate('mode not found');
            $sing['award_amount'] = $award ? $award['amount'] : translate('amount not available'); 

            if ($sing['status'] == 1) {
                $sing['status'] = '<span class="badge bg-warning text-muted">' . translate('EARRING') . '</span>';
            } elseif ($sing['status'] == 2) {
                $sing['status'] = '<span class="badge bg-success">' . translate('PAID') . '</span>';
            }
        }

        $data['sings'] = $sings;

        return view('playings/awards', $data);
    }

    public function deleteGame() {
        $modelGames = new GamesModel();
        $modelBoards = new BoardsModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $modelSings = new SingsModel();
        $modelAwards = new AwardsModel();
        $modelNotifications = new NotificationsModel();

        $gameId = $this->request->getPost('game_id');

        if (!$gameId) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'ID de juego no válido.'
            ]);
        }

        $modelNotifications->where('game', $gameId)->delete();

        $modelBoards->where('game', $gameId)->delete();

        $modelSings->where('game', $gameId)->delete();

        $modelAwards->where('game', $gameId)->delete();

        /*$cartons = $modelCartons->where('game', $gameId)->findAll();

        if ($cartons) {
            foreach ($cartons as $carton) {
                $modelNumbersCartons->where('carton', $carton['id'])->delete();
            }

            $modelCartons->where('game', $gameId)->delete();
        }*/

        $deleted = $modelGames->delete($gameId);

        if ($deleted) {
            return $this->response->setJSON([
                'success' => true,
                'message' => translate('game deleted successfully')
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'error'   => translate('error deleting game')
            ]);
        }
    }

    public function playersGet($game_id) {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelSings = new SingsModel();
        $modelUsers = new UsersModel();
        
        $game = $modelGames->find($game_id);

        if (!$game) {
            return $this->response->setStatusCode(404)->setJSON(['userCount' => 0, 'message' => translate('there is no active game')]);
        }

        $data['game'] = $game;

        $cartons = $modelCartons->where('game', $game['id'])->where('user !=', 0)->findAll();

        $userCartons = [];
        foreach ($cartons as $carton) {
            $userId = $carton['user'];
            if (!isset($userCartons[$userId])) {
                $userCartons[$userId] = [
                    'user_name' => $modelUsers->find($userId)['name'],
                    'cartons_count' => 0,
                    'bingo_count' => 0
                ];
            }
            $userCartons[$userId]['cartons_count']++;

            $userCartons[$userId]['bingo_count'] = $modelSings->where('game', $game['id'])->where('user', $userId)->countAllResults();
        }

        $data['users'] = $userCartons;

        return view('boards/players', $data);
    }

    public function gameGetAccumulated() {
        $model = new GamesModel();
        $modelModalities = new ModalitiesModel();
        $modelBoards = new BoardsModel();
        $modelCartons = new CartonsModel();
        $modelSings = new SingsModel();
        $modelAwards = new AwardsModel();

        $game = $model->find(session()->get('game_id'));

        if (!$game) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'stop', 'userCount' => 0, 'message' => translate('game not found')]);
        }

        $cartons = $modelCartons->where('game', $game['id'])->where('user !=', 0)->countAllResults();
        $accumulated = $cartons * $game['price'];
        $gameAccumulated = $accumulated - ($accumulated * systemGet('rateEarnings'));

        $response = ['gameAccumulated' => number_format($gameAccumulated, 2)];
        $response['status'] = 'success';

        $modalities = $modelModalities->whereIn('id', explode(',', $game['modalities']))->findAll();
        $modalitiesData = [];

        foreach ($modalities as &$modality) { 
            $award = $modelAwards->where('game', $game['id'])->where('modality', $modality['id'])->where('status', 1)->first();

            if ($game['award'] == 2) {
                $modality['amount'] = $award['amount'] ?? 0;
            } else {
                $modality['amount'] = $gameAccumulated * $award['amount'] / 100;
            }

            $modalitiesData[] = [
                'id' => $modality['id'],
                'amount' => number_format($modality['amount'], 2)
            ];
        }

        $response['modalities'] = $modalitiesData;

        $totalNumbersGenerated = $modelBoards->where('game', $game['id'])->countAllResults();

        if ($totalNumbersGenerated == 75) {
            return $this->response->setJSON([
                'status' => 'completed',
                'gameAccumulated' => number_format($gameAccumulated, 2),
                'modalities' => $modalitiesData, 
                'message' => translate('the game has ended, all 75 numbers have already been generated'),
            ]);
        }

        $SingsCount = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();

        $AwardsCount = $modelAwards->where('game', $game['id'])->where('status', 1)->countAllResults();

        if ($SingsCount >= $AwardsCount) {
            return $this->response->setJSON([
                'status' => 'completed',
                'gameAccumulated' => number_format($gameAccumulated, 2),
                'modalities' => $modalitiesData, 
                'message' => translate('the game is over, all the prizes have been awarded'),
            ]);
        }

        return $this->response->setJSON($response);
    }
}