case '/equipamentos-leves':
    require_once __DIR__ . '/../app/controllers/EquipamentoLeveController.php';
    (new EquipamentoLeveController())->index();
    break;

case '/equipamentos-leves/cadastrar':
    require_once __DIR__ . '/../app/controllers/EquipamentoLeveController.php';
    (new EquipamentoLeveController())->create();
    break;

case '/equipamentos-leves/salvar':
    require_once __DIR__ . '/../app/controllers/EquipamentoLeveController.php';
    (new EquipamentoLeveController())->store();
    break;
