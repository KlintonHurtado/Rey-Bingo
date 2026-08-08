<div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2">
                <i class="fa-duotone fa-solid fa-cog"></i>
                <?= translate('bingo settings'); ?>
            </h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="close"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <?php echo form_open(site_url() . 'home/settingsSubmit', array('enctype' => 'multipart/form-data', 'id' => 'settings-form'));?>
                
                <?= csrf_field() ?>
                
                <!-- Pestañas de navegación -->
                <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                            <i class="fa-duotone fa-solid fa-info-circle"></i> <?= translate('general'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab">
                            <i class="fa-duotone fa-solid fa-credit-card"></i> <?= translate('payments'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="game-tab" data-bs-toggle="tab" data-bs-target="#game" type="button" role="tab">
                            <i class="fa-duotone fa-solid fa-gamepad"></i> <?= translate('game'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button" role="tab">
                            <i class="fa-duotone fa-solid fa-dollar-sign"></i> <?= translate('financial'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="features-tab" data-bs-toggle="tab" data-bs-target="#features" type="button" role="tab">
                            <i class="fa-duotone fa-solid fa-toggle-on"></i> <?= translate('features'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="banks-tab" data-bs-toggle="tab" data-bs-target="#banks" type="button" role="tab">
                            <i class="fa-duotone fa-solid fa-building-columns"></i> <?= translate('banks'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="legal-tab" data-bs-toggle="tab" data-bs-target="#legal" type="button" role="tab">
                            <i class="fa-duotone fa-solid fa-scale-balanced"></i> <?= translate('legal'); ?>
                        </button>
                    </li>
                </ul>

                <!-- Contenido de las pestañas -->
                <div class="tab-content" id="settingsTabsContent">
                    
                    <!-- PESTAÑA GENERAL -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-md-7 mb-3">
                                        <label for="name" class="form-label"><?= translate('name bingo'); ?></label>
                                        <input type="text" class="form-control form-control-lg form-bingo" name="name" id="name" placeholder="<?= translate('name bingo'); ?>" value="<?= systemGet('name'); ?>">
                                        <small id="name-error" class="text-danger d-none"></small>
                                    </div>
                                    <div class="col-md-5 mb-3">
                                        <label for="contact" class="form-label"><?= translate('contact'); ?></label>
                                        <input type="text" class="form-control form-control-lg form-bingo" name="contact" id="contact" placeholder="<?= translate('contact'); ?>" value="<?= systemGet('contact'); ?>">
                                        <small id="contact-error" class="text-danger d-none"></small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label"><?= translate('phone'); ?></label>
                                        <input type="text" class="form-control form-control-lg form-bingo" name="phone" id="phone" placeholder="<?= translate('phone'); ?>" value="<?= systemGet('phone'); ?>">
                                        <small id="phone-error" class="text-danger d-none"></small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label"><?= translate('email'); ?></label>
                                        <input type="email" class="form-control form-control-lg form-bingo" name="email" id="email" placeholder="<?= translate('email'); ?>" value="<?= systemGet('email'); ?>">
                                        <small id="email-error" class="text-danger d-none"></small>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label for="address" class="form-label"><?= translate('address'); ?></label>
                                        <input type="text" class="form-control form-control-lg form-bingo" name="address" id="address" placeholder="<?= translate('address'); ?>" value="<?= systemGet('address'); ?>">
                                        <small id="address-error" class="text-danger d-none"></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 my-auto text-center">
                                <div class="logo-picture">
                                    <img id="logoImage" src="<?= getLogo(); ?>" alt="img">
                                    <label for="fileInput" class="edit-button logo"><i class="fa-duotone fa-edit"></i></label>
                                    <input type="file" id="fileInput" accept="image/*" style="display: none;" onchange="previewlogoImage(event)">
                                    <input type="hidden" id="logo_image_input" name="logo">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="city" class="form-label"><?= translate('city'); ?></label>
                                <input type="text" class="form-control form-control-lg form-bingo" name="city" id="city" placeholder="<?= translate('city'); ?>" value="<?= systemGet('city'); ?>">
                                <small id="city-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="zipcode" class="form-label"><?= translate('zipcode'); ?></label>
                                <input type="text" class="form-control form-control-lg form-bingo" name="zipcode" id="zipcode" placeholder="<?= translate('zipcode'); ?>" value="<?= systemGet('zipcode'); ?>">
                                <small id="zipcode-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="country" class="form-label"><?= translate('country'); ?></label>
                                <input type="text" class="form-control form-control-lg form-bingo" name="country" id="country" placeholder="<?= translate('country'); ?>" value="<?= systemGet('country'); ?>">
                                <small id="country-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="language" class="form-label"><?= translate('language'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="language" id="language">
                                    <option <?= systemGet('language') == 'english' ? 'selected' : '' ?> value="english"><?= translate('english'); ?></option>
                                    <option <?= systemGet('language') == 'spanish' ? 'selected' : '' ?> value="spanish"><?= translate('spanish'); ?></option>
                                </select>
                                <small id="language-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="accountInstagram" class="form-label"><?= translate('Instagram account'); ?></label>
                                <input type="text" class="form-control form-control-lg form-bingo" name="accountInstagram" id="accountInstagram" placeholder="<?= translate('Instagram account'); ?>" value="<?= systemGet('accountInstagram'); ?>">
                                <small id="accountInstagram-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="linkGroup" class="form-label"><?= translate('WhatsApp group link'); ?></label>
                                <input type="url" class="form-control form-control-lg form-bingo" name="linkGroup" id="linkGroup" placeholder="<?= translate('WhatsApp group link'); ?>" value="<?= systemGet('linkGroup'); ?>">
                                <small id="linkGroup-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-12 mb-2">
                                <hr class="my-2">
                                <h6 class="text-start mb-2"><?= translate('client domain login'); ?></h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="activateClientDomain" class="form-label"><?= translate('activate client domain'); ?></label>
                                <select class="form-control form-control-lg form-bingo" name="activateClientDomain" id="activateClientDomain">
                                    <option <?= (int) systemGet('activateClientDomain') === 1 ? 'selected' : '' ?> value="1"><?= translate('yes'); ?></option>
                                    <option <?= (int) systemGet('activateClientDomain') !== 1 ? 'selected' : '' ?> value="0"><?= translate('not'); ?></option>
                                </select>
                                <small id="activateClientDomain-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="clientDomain" class="form-label"><?= translate('client domain'); ?></label>
                                <input type="text" class="form-control form-control-lg form-bingo" name="clientDomain" id="clientDomain" placeholder="juego.micliente.com" value="<?= esc(systemGet('clientDomain')); ?>">
                                <small class="text-muted d-block mt-1"><?= translate('client domain help'); ?></small>
                                <small id="clientDomain-error" class="text-danger d-none"></small>
                            </div>
                            <?php if (bingo_client_domain() !== ''): ?>
                            <div class="col-md-12 mb-3">
                                <div class="alert alert-info text-start mb-0 py-2 px-3">
                                    <?= translate('official client access'); ?>:
                                    <strong><?= esc(bingo_client_login_url('/signin')); ?></strong>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- PESTAÑA PAGOS -->
                    <div class="tab-pane fade" id="payment" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-6 mb-3">
                                <label for="bank" class="form-label"><?= translate('bank'); ?> <?= translate('default'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="bank" id="bank">
                                    <option value=""><?= translate('select bank'); ?></option>
                                    <?php foreach ($banks as $bank): ?>
                                        <option <?= systemGet('bank') == $bank['id'] ? 'selected' : '' ?> value="<?= $bank['id'] ?>"><?= $bank['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small id="bank-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="method" class="form-label"><?= translate('payment method'); ?> <?= translate('default'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="method" id="method">
                                    <option value=""><?= translate('payment method'); ?></option>
                                    <option <?= systemGet('method') == 'transfer' ? 'selected' : '' ?> value="transfer"><?= translate('transfer'); ?></option>
                                </select>
                                <small id="method-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="activatePayPal" class="form-label"><?= translate('PayPal'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="activatePayPal" id="activatePayPal">
                                    <option <?= systemGet('activatePayPal') == 1 ? 'selected' : '' ?> value="1"><?= translate('active'); ?></option>
                                    <option <?= systemGet('activatePayPal') == 0 ? 'selected' : '' ?> value="0"><?= translate('Inactive'); ?></option>
                                </select>
                                <small id="activatePayPal-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 mb-3" id="paypal-fields">
                                <label for="idPayPal" class="form-label"><?= translate('PayPal'); ?> <?= translate('Client ID'); ?></label>
                                <input type="text" class="form-control form-control-lg form-bingo" name="idPayPal" id="idPayPal" placeholder="<?= translate('PayPal'); ?> <?= translate('Client ID'); ?>" value="<?= systemGet('idPayPal'); ?>">
                                <small id="idPayPal-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="secretPayPal" class="form-label"><?= translate('PayPal'); ?> <?= translate('Secret'); ?></label>
                                <input type="text" class="form-control form-control-lg form-bingo" name="secretPayPal" id="secretPayPal" placeholder="<?= translate('PayPal'); ?> <?= translate('Secret'); ?>" value="<?= systemGet('secretPayPal'); ?>">
                                <small id="secretPayPal-error" class="text-danger d-none"></small>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑA JUEGO -->
                    <div class="tab-pane fade" id="game" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-4 mb-3">
                                <label for="singBingoOnlyLastBall" class="form-label"><?= translate('sing bingo only with the last ball'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="singBingoOnlyLastBall" id="singBingoOnlyLastBall">
                                    <option <?= systemGet('singBingoOnlyLastBall') == 1 ? 'selected' : '' ?> value="1"><?= translate('yes'); ?></option>
                                    <option <?= systemGet('singBingoOnlyLastBall') == 0 ? 'selected' : '' ?> value="0"><?= translate('not'); ?></option>
                                </select>
                                <small id="singBingoOnlyLastBall-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="numberSings" class="form-label"><?= translate('number sings'); ?></label>
                                <input type="number" class="form-control form-control-lg form-bingo" name="numberSings" id="numberSings" placeholder="<?= translate('number sings'); ?>" value="<?= systemGet('numberSings'); ?>">
                                <small id="numberSings-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="singBall" class="form-label"><?= translate('sing ball every'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="singBall" id="singBall">
                                    <option value=""><?= translate('sing ball'); ?></option>
                                    <option <?= systemGet('singBall') == '5000-2500' ? 'selected' : '' ?> value="5000-2500">5 <?= translate('seconds'); ?></option>
                                    <option <?= systemGet('singBall') == '10000-5000' ? 'selected' : '' ?> value="10000-5000">10 <?= translate('seconds'); ?></option>
                                    <option <?= systemGet('singBall') == '15000-5000' ? 'selected' : '' ?> value="15000-5000">15 <?= translate('seconds'); ?></option>
                                    <option <?= systemGet('singBall') == '20000-5000' ? 'selected' : '' ?> value="20000-5000">20 <?= translate('seconds'); ?></option>
                                    <option <?= systemGet('singBall') == '25000-5000' ? 'selected' : '' ?> value="25000-5000">25 <?= translate('seconds'); ?></option>
                                    <option <?= systemGet('singBall') == '30000-5000' ? 'selected' : '' ?> value="30000-5000">30 <?= translate('seconds'); ?></option>
                                </select>
                                <small id="singBall-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 mb-3 hidden">
                                <label for="generateCartons" class="form-label"><?= translate('generate cartons'); ?></label>
                                <input type="number" class="form-control form-control-lg form-bingo" name="generateCartons" id="generateCartons" placeholder="<?= translate('generate cartons'); ?>" value="<?= systemGet('generateCartons'); ?>">
                                <small id="generateCartons-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="maxCartons" class="form-label"><?= translate('maximum cards per player'); ?></label>
                                <input type="number" class="form-control form-control-lg form-bingo" name="maxCartons" id="maxCartons" placeholder="<?= translate('max cartons'); ?>" value="<?= systemGet('maxCartons'); ?>">
                                <small id="maxCartons-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="activateAddGames" class="form-label"><?= translate('activate auto creation of games'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="activateAddGames" id="activateAddGames">
                                    <option <?= systemGet('activateAddGames') == 1 ? 'selected' : '' ?> value="1"><?= translate('active'); ?></option>
                                    <option <?= systemGet('activateAddGames') == 0 ? 'selected' : '' ?> value="0"><?= translate('Inactive'); ?></option>
                                </select>
                                <small id="activateAddGames-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="addGamesTime" class="form-label"><?= translate('create game every'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="addGamesTime" id="addGamesTime">
                                    <option <?= systemGet('addGamesTime') == 5 ? 'selected' : '' ?> value="5">5 <?= translate('minutes'); ?></option>
                                    <option <?= systemGet('addGamesTime') == 10 ? 'selected' : '' ?> value="10">10 <?= translate('minutes'); ?></option>
                                    <option <?= systemGet('addGamesTime') == 15 ? 'selected' : '' ?> value="15">15 <?= translate('minutes'); ?></option>
                                    <option <?= systemGet('addGamesTime') == 20 ? 'selected' : '' ?> value="20">20 <?= translate('minutes'); ?></option>
                                    <option <?= systemGet('addGamesTime') == 25 ? 'selected' : '' ?> value="25">25 <?= translate('minutes'); ?></option>
                                    <option <?= systemGet('addGamesTime') == 30 ? 'selected' : '' ?> value="30">30 <?= translate('minutes'); ?></option>
                                    <option <?= systemGet('addGamesTime') == 35 ? 'selected' : '' ?> value="35">35 <?= translate('minutes'); ?></option>
                                    <option <?= systemGet('addGamesTime') == 40 ? 'selected' : '' ?> value="40">40 <?= translate('minutes'); ?></option>
                                    <option <?= systemGet('addGamesTime') == 45 ? 'selected' : '' ?> value="45">45 <?= translate('minutes'); ?></option>
                                    <option <?= systemGet('addGamesTime') == 50 ? 'selected' : '' ?> value="50">50 <?= translate('minutes'); ?></option>
                                    <option <?= systemGet('addGamesTime') == 55 ? 'selected' : '' ?> value="55">55 <?= translate('minutes'); ?></option>
                                    <option <?= systemGet('addGamesTime') == 60 ? 'selected' : '' ?> value="60">1 <?= translate('hour'); ?></option>
                                    <option <?= systemGet('addGamesTime') == 120 ? 'selected' : '' ?> value="120">2 <?= translate('hours'); ?></option>
                                    <option <?= systemGet('addGamesTime') == 180 ? 'selected' : '' ?> value="180">3 <?= translate('hours'); ?></option>
                                    <option <?= systemGet('addGamesTime') == 240 ? 'selected' : '' ?> value="240">4 <?= translate('hours'); ?></option>
                                    <option <?= systemGet('addGamesTime') == 300 ? 'selected' : '' ?> value="300">5 <?= translate('hours'); ?></option>
                                    <option <?= systemGet('addGamesTime') == 1440 ? 'selected' : '' ?> value="1440">1 <?= translate('day'); ?></option>
                                </select>
                                <small id="addGamesTime-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="addGamesFrom" class="form-label"><?= translate('create games from'); ?></label>
                                <input type="time" class="form-control form-control-lg form-bingo" name="addGamesFrom" id="addGamesFrom" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('hour')); ?>" autocomplete="off" value="<?= systemGet('addGamesFrom'); ?>">
                                <small id="addGamesFrom-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="addGamesTo" class="form-label"><?= translate('create games to'); ?></label>
                                <input type="time" class="form-control form-control-lg form-bingo" name="addGamesTo" id="addGamesTo" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('hour')); ?>" autocomplete="off" value="<?= systemGet('addGamesTo'); ?>">
                                <small id="addGamesTo-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="priceRanges" class="form-label"><?= translate('game price range'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="priceRanges" id="priceRanges">
                                    <option <?= systemGet('priceRanges') == 1 ? 'selected' : '' ?> value="1"><?= systemGet('currency'); ?> 0.25 a <?= systemGet('currency'); ?> 5</option>
                                    <option <?= systemGet('priceRanges') == 2 ? 'selected' : '' ?> value="2"><?= systemGet('currency'); ?> 0.25 a <?= systemGet('currency'); ?> 3</option>
                                    <option <?= systemGet('priceRanges') == 3 ? 'selected' : '' ?> value="3"><?= systemGet('currency'); ?> 0.5 a <?= systemGet('currency'); ?> 5</option>
                                    <option <?= systemGet('priceRanges') == 4 ? 'selected' : '' ?> value="4"><?= systemGet('currency'); ?> 0.25 a <?= systemGet('currency'); ?> 2</option>
                                    <option <?= systemGet('priceRanges') == 5 ? 'selected' : '' ?> value="5"><?= systemGet('currency'); ?> 1 a <?= systemGet('currency'); ?> 5</option>
                                </select>
                                <small id="priceRanges-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-12 mt-3 mb-2">
                                <h6 class="text-muted border-bottom pb-2"><i class="fa-duotone fa-solid fa-robot"></i> <?= translate('auto game configuration'); ?></h6>
                            </div>
                            <input type="hidden" name="autoGameRoom" value="<?= esc(systemGet('autoGameRoom') ?? ''); ?>">
                            <?php
                                $selectedModalities = array_values(array_filter(explode(',', (string) (systemGet('autoGameModalities') ?? ''))));
                                if (empty($selectedModalities)) {
                                    echo '<input type="hidden" name="autoGameModalities[]" value="">';
                                } else {
                                    foreach ($selectedModalities as $modId) {
                                        echo '<input type="hidden" name="autoGameModalities[]" value="' . esc($modId) . '">';
                                    }
                                }
                            ?>
                            <div class="col-md-4 mb-3">
                                <label for="autoGameAwardType" class="form-label"><?= translate('award type'); ?></label>
                                <select class="form-control form-control-lg form-bingo" name="autoGameAwardType" id="autoGameAwardType">
                                    <option <?= systemGet('autoGameAwardType') == 1 ? 'selected' : '' ?> value="1"><?= translate('accumulated'); ?></option>
                                    <option <?= systemGet('autoGameAwardType') == 2 ? 'selected' : '' ?> value="2"><?= translate('amount'); ?></option>
                                </select>
                                <small id="autoGameAwardType-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="autoGameAwardValue" class="form-label"><?= translate('amount'); ?></label>
                                <input type="number" step="0.01" min="0" class="form-control form-control-lg form-bingo" name="autoGameAwardValue" id="autoGameAwardValue" placeholder="<?= translate('amount'); ?>" value="<?= esc(systemGet('autoGameAwardValue') !== null ? systemGet('autoGameAwardValue') : '100'); ?>">
                                <small id="autoGameAwardValue-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="autoGameMinPlayers" class="form-label"><?= translate('minimum players'); ?></label>
                                <input type="number" class="form-control form-control-lg form-bingo" name="autoGameMinPlayers" id="autoGameMinPlayers" min="1" max="9999" value="<?= esc(systemGet('autoGameMinPlayers') ?: '10'); ?>">
                                <small id="autoGameMinPlayers-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="autoGameMinCartons" class="form-label"><?= translate('minimum cartons'); ?></label>
                                <input type="number" class="form-control form-control-lg form-bingo" name="autoGameMinCartons" id="autoGameMinCartons" min="1" max="99999" value="<?= esc(systemGet('autoGameMinCartons') ?: '10'); ?>">
                                <small id="autoGameMinCartons-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="autoGameAllowRoulette" class="form-label"><?= translate('allow roulette cartons'); ?></label>
                                <div class="form-check mt-2" style="padding-left: 20px;">
                                    <input class="form-check-input" type="checkbox" name="autoGameAllowRoulette" id="autoGameAllowRoulette" value="1" <?= (systemGet('autoGameAllowRoulette') ?? 1) == 1 ? 'checked' : '' ?> style="width: 24px; height: 24px; border: 3px solid #6236ff; border-radius: 6px; cursor: pointer;">
                                </div>
                                <small id="autoGameAllowRoulette-error" class="text-danger d-none"></small>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑA FINANCIERA -->
                    <div class="tab-pane fade" id="financial" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h6><?= translate('currency and rates'); ?></h6>
                                <hr class="my-1">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="currency" class="form-label"><?= translate('currency'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="currency" id="currency">
                                    <option <?= systemGet('currency') == 'USD' ? 'selected' : '' ?> value="USD"><?= translate('dollars'); ?> - USD</option>
                                    <option <?= systemGet('currency') == 'Bs' ? 'selected' : '' ?> value="Bs"><?= translate('bolivars'); ?> - Bs</option>
                                    <option <?= systemGet('currency') == 'BGC' ? 'selected' : '' ?> value="BGC"><?= translate('Bingo Coin'); ?> - BGC</option>
                                </select>
                                <small id="currency-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="rateExchange" class="form-label"><?= translate('exchange rate'); ?></label>
                                <input type="number" step="0.01" class="form-control form-control-lg form-bingo" name="rateExchange" id="rateExchange" placeholder="<?= translate('exchange rate'); ?>" value="<?= systemGet('rateExchange'); ?>">
                                <small id="rateExchange-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="valueBGC" class="form-label"><?= translate('Bingo Coin value'); ?></label>
                                <input type="number" step="0.01" class="form-control form-control-lg form-bingo" name="valueBGC" id="valueBGC" placeholder="<?= translate('Bingo Coin value'); ?>" value="<?= systemGet('valueBGC'); ?>">
                                <small id="valueBGC-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="activateMinimumDeposit" class="form-label"><?= translate('minimum deposit'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="activateMinimumDeposit" id="activateMinimumDeposit">
                                    <option <?= systemGet('activateMinimumDeposit') == '1' ? 'selected' : '' ?> value="1"><?= translate('yes'); ?></option>
                                    <option <?= systemGet('activateMinimumDeposit') == '0' ? 'selected' : '' ?> value="0"><?= translate('not'); ?></option>
                                </select>
                                <small id="activateMinimumDeposit-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-12">
                                <h6>Abono de bienvenida</h6>
                                <hr class="my-1">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="registrationBonus" class="form-label">Bono al registrarse</label>
                                <input type="number" step="0.01" min="0" class="form-control form-control-lg form-bingo" name="registrationBonus" id="registrationBonus" placeholder="0.00" value="<?= esc(systemGet('registrationBonus') ?? '0'); ?>">
                                <small class="text-muted"><?= translate('registration bonus help short'); ?></small>
                                <small id="registrationBonus-error" class="text-danger d-none"></small>
                            </div>

                            <?php
                            $commissionRatePct = static function (string $key, ?string $fallbackKey = null): float {
                                $raw = systemGet($key);
                                if (($raw === null || $raw === '') && $fallbackKey !== null) {
                                    $raw = systemGet($fallbackKey);
                                }

                                return round((float) ($raw ?? 0) * 100, 2);
                            };
                            $opAffiliatePct = $commissionRatePct('rateOperatorGgrAffiliate', 'rateOperatorCommission');
                            $opRechargePct = $commissionRatePct('rateOperatorRecharge');
                            $opWithdrawPct = $commissionRatePct('rateOperatorWithdraw');
                            $pvAffiliatePct = $commissionRatePct('rateStoreGgrAffiliate', 'rateStoreGgrCommission');
                            $pvRechargePct = $commissionRatePct('rateStoreCommission');
                            $pvWithdrawPct = $commissionRatePct('rateStorePrizeCommission');
                            $ggrSettlementMode = bingo_ggr_settlement_mode();
                            if ($ggrSettlementMode === 'immediate') {
                                $ggrSettlementMode = 'daily';
                            }
                            ?>

                            <div class="col-md-12">
                                <h6><?= translate('operator settings'); ?></h6>
                                <hr class="my-1">
                                <small class="text-muted d-block mb-2"><?= translate('operator settings help hierarchical'); ?></small>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label for="rateOperatorGgrAffiliate" class="form-label"><?= translate('operator ggr affiliate rate'); ?> %</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-lg form-bingo js-op-rate" name="rateOperatorGgrAffiliate" id="rateOperatorGgrAffiliate" data-category="affiliate" value="<?= $opAffiliatePct; ?>">
                                <small id="rateOperatorGgrAffiliate-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label for="rateOperatorRecharge" class="form-label"><?= translate('operator recharge rate'); ?> %</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-lg form-bingo js-op-rate" name="rateOperatorRecharge" id="rateOperatorRecharge" data-category="recharge" value="<?= $opRechargePct; ?>">
                                <small id="rateOperatorRecharge-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label for="rateOperatorWithdraw" class="form-label"><?= translate('operator withdraw rate'); ?> %</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-lg form-bingo js-op-rate" name="rateOperatorWithdraw" id="rateOperatorWithdraw" data-category="withdraw" value="<?= $opWithdrawPct; ?>">
                                <small id="rateOperatorWithdraw-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label for="ggrSettlementMode" class="form-label"><?= translate('ggr settlement mode'); ?></label>
                                <select class="form-control form-control-lg form-bingo" name="ggrSettlementMode" id="ggrSettlementMode">
                                    <option value="daily" <?= $ggrSettlementMode === 'daily' ? 'selected' : ''; ?>><?= translate('ggr settlement daily'); ?></option>
                                    <option value="weekly" <?= $ggrSettlementMode === 'weekly' ? 'selected' : ''; ?>><?= translate('ggr settlement weekly'); ?></option>
                                    <option value="monthly" <?= $ggrSettlementMode === 'monthly' ? 'selected' : ''; ?>><?= translate('ggr settlement monthly'); ?></option>
                                </select>
                            </div>

                            <div class="col-md-12 mt-2">
                                <h6><?= translate('point of sale settings'); ?></h6>
                                <hr class="my-1">
                                <small class="text-muted d-block mb-2"><?= translate('point of sale settings help hierarchical'); ?></small>
                            </div>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <label for="rateStoreGgrAffiliate" class="form-label"><?= translate('store ggr affiliate rate'); ?> %</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-lg form-bingo js-pv-rate" name="rateStoreGgrAffiliate" id="rateStoreGgrAffiliate" data-category="affiliate" data-op-field="rateOperatorGgrAffiliate" value="<?= $pvAffiliatePct; ?>">
                                <small class="text-muted d-block js-margin-hint" data-category="affiliate"><?= translate('operator margin label'); ?>: <span class="js-margin-value">0</span>%</small>
                                <small id="rateStoreGgrAffiliate-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <label for="rateStoreCommission" class="form-label"><?= translate('store recharge commission rate'); ?> %</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-lg form-bingo js-pv-rate" name="rateStoreCommission" id="rateStoreCommission" data-category="recharge" data-op-field="rateOperatorRecharge" value="<?= $pvRechargePct; ?>">
                                <small class="text-muted d-block js-margin-hint" data-category="recharge"><?= translate('operator margin label'); ?>: <span class="js-margin-value">0</span>%</small>
                                <small id="rateStoreCommission-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <label for="rateStorePrizeCommission" class="form-label"><?= translate('store withdraw prize commission rate'); ?> %</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-lg form-bingo js-pv-rate" name="rateStorePrizeCommission" id="rateStorePrizeCommission" data-category="withdraw" data-op-field="rateOperatorWithdraw" value="<?= $pvWithdrawPct; ?>">
                                <small class="text-muted d-block js-margin-hint" data-category="withdraw"><?= translate('operator margin label'); ?>: <span class="js-margin-value">0</span>%</small>
                                <small id="rateStorePrizeCommission-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-12">
                                <h6><?= translate('business settings'); ?></h6>
                                <hr class="my-1">
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label for="rateEarnings" class="form-label"><?= translate('earnings rate'); ?> %</label>
                                <input type="number" step="0.01" class="form-control form-control-lg form-bingo" name="rateEarnings" id="rateEarnings" placeholder="<?= translate('earnings rate'); ?>" value="<?= systemGet('rateEarnings') * 100; ?>">
                                <small id="rateEarnings-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label for="rateReferrals" class="form-label"><?= translate('referrals rate'); ?> %</label>
                                <input type="number" step="0.01" class="form-control form-control-lg form-bingo" name="rateReferrals" id="rateReferrals" placeholder="<?= translate('referrals rate'); ?>" value="<?= systemGet('rateReferrals') * 100; ?>">
                                <small id="rateReferrals-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label for="rateBGC" class="form-label"><?= translate('BGC rate'); ?> %</label>
                                <input type="number" step="0.01" class="form-control form-control-lg form-bingo" name="rateBGC" id="rateBGC" placeholder="<?= translate('BGC rate'); ?>" value="<?= systemGet('rateBGC') * 100; ?>">
                                <small id="rateBGC-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-12">
                                <h6><?= translate('deposit limits'); ?></h6>
                                <hr class="my-1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="minimumDeposit" class="form-label"><?= translate('minimum deposit'); ?></label>
                                <input type="number" step="0.01" class="form-control form-control-lg form-bingo" name="minimumDeposit" id="minimumDeposit" placeholder="<?= translate('minimum deposit'); ?>" value="<?= systemGet('minimumDeposit'); ?>">
                                <small id="minimumDeposit-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="maximumDeposit" class="form-label"><?= translate('maximum deposit'); ?></label>
                                <input type="number" step="0.01" class="form-control form-control-lg form-bingo" name="maximumDeposit" id="maximumDeposit" placeholder="<?= translate('maximum deposit'); ?>" value="<?= systemGet('maximumDeposit'); ?>">
                                <small id="maximumDeposit-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-12">
                                <h6><?= translate('withdrawal limits'); ?></h6>
                                <hr class="my-1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="minimumRetire" class="form-label"><?= translate('minimum withdrawal'); ?></label>
                                <input type="number" step="0.01" class="form-control form-control-lg form-bingo" name="minimumRetire" id="minimumRetire" placeholder="<?= translate('minimum withdrawal'); ?>" value="<?= systemGet('minimumRetire'); ?>">
                                <small id="minimumRetire-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="maximumRetire" class="form-label"><?= translate('maximum withdrawal'); ?></label>
                                <input type="number" step="0.01" class="form-control form-control-lg form-bingo" name="maximumRetire" id="maximumRetire" placeholder="<?= translate('maximum withdrawal'); ?>" value="<?= systemGet('maximumRetire'); ?>">
                                <small id="maximumRetire-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-12">
                                <h6><?= translate('transfer limits'); ?></h6>
                                <hr class="my-1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="minimumTransfer" class="form-label"><?= translate('minimum transfer'); ?></label>
                                <input type="number" step="0.01" class="form-control form-control-lg form-bingo" name="minimumTransfer" id="minimumTransfer" placeholder="<?= translate('minimum transfer'); ?>" value="<?= systemGet('minimumTransfer'); ?>">
                                <small id="minimumTransfer-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="maximumTransfer" class="form-label"><?= translate('maximum transfer'); ?></label>
                                <input type="number" step="0.01" class="form-control form-control-lg form-bingo" name="maximumTransfer" id="maximumTransfer" placeholder="<?= translate('maximum transfer'); ?>" value="<?= systemGet('maximumTransfer'); ?>">
                                <small id="maximumTransfer-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-12">
                                <h6><?= translate('transaction controls'); ?></h6>
                                <hr class="my-1">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="activateDeposit" class="form-label"><?= translate('activate deposits'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="activateDeposit" id="activateDeposit">
                                    <option <?= systemGet('activateDeposit') == '1' ? 'selected' : '' ?> value="1"><?= translate('active'); ?></option>
                                    <option <?= systemGet('activateDeposit') == '0' ? 'selected' : '' ?> value="0"><?= translate('inactive'); ?></option>
                                </select>
                                <small id="activateDeposit-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="activateRetire" class="form-label"><?= translate('activate withdrawals'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="activateRetire" id="activateRetire">
                                    <option <?= systemGet('activateRetire') == '1' ? 'selected' : '' ?> value="1"><?= translate('active'); ?></option>
                                    <option <?= systemGet('activateRetire') == '0' ? 'selected' : '' ?> value="0"><?= translate('inactive'); ?></option>
                                </select>
                                <small id="activateRetire-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="activateTransfer" class="form-label"><?= translate('activate transfers'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="activateTransfer" id="activateTransfer">
                                    <option <?= systemGet('activateTransfer') == '1' ? 'selected' : '' ?> value="1"><?= translate('active'); ?></option>
                                    <option <?= systemGet('activateTransfer') == '0' ? 'selected' : '' ?> value="0"><?= translate('inactive'); ?></option>
                                </select>
                                <small id="activateTransfer-error" class="text-danger d-none"></small>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑA CARACTERÍSTICAS -->
                    <div class="tab-pane fade" id="features" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h6><?= translate('game features'); ?></h6>
                                <hr class="my-1">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="activateAlgorithm" class="form-label"><?= translate('activate algorithm'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="activateAlgorithm" id="activateAlgorithm">
                                    <option <?= systemGet('activateAlgorithm') == '1' ? 'selected' : '' ?> value="1"><?= translate('active'); ?></option>
                                    <option <?= systemGet('activateAlgorithm') == '0' ? 'selected' : '' ?> value="0"><?= translate('inactive'); ?></option>
                                </select>
                                <small id="activateAlgorithm-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="activateCron" class="form-label"><?= translate('activate cron'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="activateCron" id="activateCron">
                                    <option <?= systemGet('activateCron') == '1' ? 'selected' : '' ?> value="1"><?= translate('active'); ?></option>
                                    <option <?= systemGet('activateCron') == '0' ? 'selected' : '' ?> value="0"><?= translate('inactive'); ?></option>
                                </select>
                                <small id="activateCron-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="activateRoomCards" class="form-label"><?= translate('activate room cards'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="activateRoomCards" id="activateRoomCards">
                                    <option <?= systemGet('activateRoomCards') == '1' ? 'selected' : '' ?> value="1"><?= translate('active'); ?></option>
                                    <option <?= systemGet('activateRoomCards') == '0' ? 'selected' : '' ?> value="0"><?= translate('inactive'); ?></option>
                                </select>
                                <small id="activateRoomCards-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="activateShareGame" class="form-label"><?= translate('activate share game'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="activateShareGame" id="activateShareGame">
                                    <option <?= systemGet('activateShareGame') == '1' ? 'selected' : '' ?> value="1"><?= translate('active'); ?></option>
                                    <option <?= systemGet('activateShareGame') == '0' ? 'selected' : '' ?> value="0"><?= translate('inactive'); ?></option>
                                </select>
                                <small id="activateShareGame-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="activateRoulette" class="form-label"><?= translate('activate roulette'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="activateRoulette" id="activateRoulette">
                                    <option <?= systemGet('activateRoulette') == '1' ? 'selected' : '' ?> value="1"><?= translate('active'); ?></option>
                                    <option <?= systemGet('activateRoulette') == '0' ? 'selected' : '' ?> value="0"><?= translate('inactive'); ?></option>
                                </select>
                                <small id="activateRoulette-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <h6 class="mb-0"><?= translate('roulette prizes'); ?></h6>
                                        <small class="text-muted"><?= translate('roulette prizes help'); ?></small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary" id="roulette-prize-add">
                                        <i class="fa-duotone fa-plus"></i> <?= translate('add prize'); ?>
                                    </button>
                                </div>
                                <div class="table-responsive mt-2">
                                    <table class="table table-sm align-middle mb-1">
                                        <thead>
                                            <tr>
                                                <th><?= translate('prize label'); ?></th>
                                                <th style="width:140px"><?= translate('cartons'); ?></th>
                                                <th style="width:60px"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="roulette-prizes-list">
                                            <?php foreach (bingo_roulette_prizes() as $prize) : ?>
                                                <tr class="roulette-prize-row">
                                                    <td>
                                                        <input type="text" class="form-control form-bingo" name="roulette_prize_label[]" value="<?= esc($prize['label']); ?>" maxlength="40" required>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-bingo" name="roulette_prize_cartons[]" value="<?= (int) $prize['cartons']; ?>" min="0" max="100" step="1" required>
                                                        <small class="text-muted">0 = <?= translate('no prize'); ?></small>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-danger roulette-prize-remove" title="<?= translate('delete'); ?>">
                                                            <i class="fa-duotone fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <small id="roulettePrizes-error" class="text-danger d-none"></small>
                                <small class="text-muted d-block" id="roulette-prizes-count"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="activateJoinGroup" class="form-label"><?= translate('activate join group'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="activateJoinGroup" id="activateJoinGroup">
                                    <option <?= systemGet('activateJoinGroup') == '1' ? 'selected' : '' ?> value="1"><?= translate('active'); ?></option>
                                    <option <?= systemGet('activateJoinGroup') == '0' ? 'selected' : '' ?> value="0"><?= translate('inactive'); ?></option>
                                </select>
                                <small id="activateJoinGroup-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-12">
                                <h6><?= translate('user features'); ?></h6>
                                <hr class="my-1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="activateCompleteProfile" class="form-label"><?= translate('activate complete profile'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="activateCompleteProfile" id="activateCompleteProfile">
                                    <option <?= systemGet('activateCompleteProfile') == '1' ? 'selected' : '' ?> value="1"><?= translate('active'); ?></option>
                                    <option <?= systemGet('activateCompleteProfile') == '0' ? 'selected' : '' ?> value="0"><?= translate('inactive'); ?></option>
                                </select>
                                <small id="activateCompleteProfile-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="activateInstallPWA" class="form-label"><?= translate('activate install PWA'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="activateInstallPWA" id="activateInstallPWA">
                                    <option <?= systemGet('activateInstallPWA') == '1' ? 'selected' : '' ?> value="1"><?= translate('active'); ?></option>
                                    <option <?= systemGet('activateInstallPWA') == '0' ? 'selected' : '' ?> value="0"><?= translate('inactive'); ?></option>
                                </select>
                                <small id="activateInstallPWA-error" class="text-danger d-none"></small>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑA BANCOS -->
                    <div class="tab-pane fade" id="banks" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6><?= translate('bank accounts enabled for deposits and withdrawals'); ?></h6>
                                    <button type="button" class="btn btn-primary" onclick="bankGet();">
                                        <i class="fa-duotone fa-solid fa-plus"></i> <?= translate('add bank'); ?>
                                    </button>
                                </div>
                                <hr class="my-1">
                            </div>
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr class="text-uppercase">
                                                <th class="text-left"><?= translate('bank info'); ?></th>
                                                <th class="text-center"><?= translate('predetermined'); ?></th>
                                                <th class="text-center"><?= translate('options'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody id="settings-list">
                                            <?php if (!empty($banks)) : ?>
                                                <?php foreach ($banks as $bank) : ?>
                                                    <tr id="bank-row-<?= $bank['id'] ?>">
                                                        <td class="text-left">
                                                            <div class="p-2 d-flex flex-row align-items-center" style="border-radius: 10px; width: 100%;">
                                                                <div style="flex: 0 0 50px; text-align:center;">
                                                                    <?php if (!empty($bank['logo'])): ?>
                                                                        <img src="<?= site_url('uploads/banks/' . $bank['logo']) ?>" alt="logo banco" class="img-fluid" style="width:50px; height:50px; object-fit:cover;">
                                                                    <?php else: ?>
                                                                        <i class="fa-duotone fa-solid fa-building-columns fs-1 text-white"></i>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div style="flex: 1; padding-left: 15px;">
                                                                    <h6 class="mb-1"><strong><?= translate('name bank'); ?>:</strong> <?= esc($bank['name']) ?></h6>
                                                                    <small class="mb-0"><strong><?= translate("account"); ?>:</strong> <?= esc($bank['account']) ?> - <strong><?= translate("holder"); ?>:</strong> <?= esc($bank['holder']) ?></small><br>
                                                                    <small class="mb-0"><strong><?= translate("document"); ?>:</strong> <?= esc($bank['document']) ?> - <strong><?= translate("type"); ?>:</strong> <?= esc($bank['type'] ?? '') ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <?= systemGet('bank') == $bank['id'] ? '<span class="badge bg-success p-2 text-uppercase fs-6">' . translate('yes') . '</span>' : '<span class="badge bg-secondary p-2 text-uppercase fs-6">' . translate('not') . '</span>' ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="btn-group" role="group">
                                                                <button type="button" class="btn btn-primary" onclick="updateBank(<?= $bank['id'] ?>)" title="<?= translate('update'); ?>">
                                                                    <i class="fa-duotone fa-edit fs-5"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-danger" onclick="deleteBank(<?= $bank['id'] ?>)" title="<?= translate('delete'); ?>">
                                                                    <i class="fa-duotone fa-solid fa-trash fs-5"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <tr id="not-list">
                                                    <td colspan="3" class="text-center"><?= translate('no data available'); ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑA LEGAL / TyC -->
                    <div class="tab-pane fade" id="legal" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-12 mb-3">
                                <h6><?= translate('terms and promotions'); ?></h6>
                                <p class="text-muted mb-2"><?= translate('edit terms and promotions description'); ?></p>
                                <a href="<?= site_url('legal/admin'); ?>" class="btn btn-primary btn-bingo">
                                    <i class="fa-duotone fa-solid fa-pen-to-square"></i> <?= translate('open legal editor'); ?>
                                </a>
                                <a href="<?= site_url('terminos'); ?>" target="_blank" rel="noopener" class="btn btn-outline-primary ms-1">
                                    <?= translate('view terms'); ?>
                                </a>
                                <a href="<?= site_url('promociones'); ?>" target="_blank" rel="noopener" class="btn btn-outline-primary ms-1">
                                    <?= translate('view promotions'); ?>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="termsRequireAccept" class="form-label"><?= translate('require terms acceptance on signup'); ?></label>
                                <select class="form-control form-control-lg form-bingo" name="termsRequireAccept" id="termsRequireAccept">
                                    <option value="1" <?= (string) (systemGet('termsRequireAccept') ?? '1') === '1' ? 'selected' : ''; ?>><?= translate('active'); ?></option>
                                    <option value="0" <?= (string) (systemGet('termsRequireAccept') ?? '1') === '0' ? 'selected' : ''; ?>><?= translate('inactive'); ?></option>
                                </select>
                                <small class="text-muted d-block"><?= translate('require terms acceptance help'); ?></small>
                                <small id="termsRequireAccept-error" class="text-danger d-none"></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="row mt-2">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary d-block w-50 btn-bingo mt-3" id="settings-button"><?= translate('update'); ?></button>
                    </div>
                </div>

            <?= form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    (function () {
        var minPrizes = 4;
        var maxPrizes = 8;

        function roulettePrizeCount() {
            return $('#roulette-prizes-list .roulette-prize-row').length;
        }

        function refreshRoulettePrizeUi() {
            var count = roulettePrizeCount();
            $('#roulette-prizes-count').text(count + ' / ' + maxPrizes + ' <?= translate('prizes'); ?> (<?= translate('min'); ?> ' + minPrizes + ')');
            $('#roulette-prize-add').prop('disabled', count >= maxPrizes);
            $('#roulette-prizes-list .roulette-prize-remove').prop('disabled', count <= minPrizes);
        }

        function buildRoulettePrizeRow(label, cartons) {
            return $(
                '<tr class="roulette-prize-row">' +
                    '<td><input type="text" class="form-control form-bingo" name="roulette_prize_label[]" value="' + (label || '') + '" maxlength="40" required></td>' +
                    '<td><input type="number" class="form-control form-bingo" name="roulette_prize_cartons[]" value="' + (cartons || 0) + '" min="0" max="100" step="1" required>' +
                    '<small class="text-muted">0 = <?= translate('no prize'); ?></small></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger roulette-prize-remove" title="<?= translate('delete'); ?>"><i class="fa-duotone fa-trash"></i></button></td>' +
                '</tr>'
            );
        }

        $(document).on('click', '#roulette-prize-add', function () {
            if (roulettePrizeCount() >= maxPrizes) {
                return;
            }
            $('#roulette-prizes-list').append(buildRoulettePrizeRow('NUEVO PREMIO', 1));
            refreshRoulettePrizeUi();
        });

        $(document).on('click', '.roulette-prize-remove', function () {
            if (roulettePrizeCount() <= minPrizes) {
                return;
            }
            $(this).closest('tr').remove();
            refreshRoulettePrizeUi();
        });

        refreshRoulettePrizeUi();
    })();

    function updateCommissionMargins() {
        $('.js-pv-rate').each(function () {
            var $pv = $(this);
            var opId = $pv.data('op-field');
            var opVal = parseFloat($('#' + opId).val());
            var pvVal = parseFloat($pv.val());
            if (isNaN(opVal)) opVal = 0;
            if (isNaN(pvVal)) pvVal = 0;
            var margin = Math.max(0, Math.round((opVal - pvVal) * 100) / 100);
            var category = $pv.data('category');
            $('.js-margin-hint[data-category="' + category + '"] .js-margin-value').text(margin.toFixed(2));
            if (pvVal > opVal) {
                $pv.addClass('is-invalid');
            } else {
                $pv.removeClass('is-invalid');
            }
        });
    }

    function validateCommissionHierarchy() {
        var ok = true;
        var firstInvalid = null;
        $('.js-pv-rate').each(function () {
            var $pv = $(this);
            var opId = $pv.data('op-field');
            var opVal = parseFloat($('#' + opId).val());
            var pvVal = parseFloat($pv.val());
            if (isNaN(opVal)) opVal = 0;
            if (isNaN(pvVal)) pvVal = 0;
            var errId = $pv.attr('id') + '-error';
            if (pvVal > opVal) {
                ok = false;
                $pv.addClass('is-invalid');
                $('#' + errId).text('<?= translate('store rate cannot exceed operator rate'); ?>').removeClass('d-none');
                if (!firstInvalid) firstInvalid = $pv.attr('id');
            }
        });
        return { ok: ok, firstInvalid: firstInvalid };
    }

    $(document).ready(function () {
        $(document).on('input change', '.js-op-rate, .js-pv-rate', updateCommissionMargins);
        updateCommissionMargins();

        // Manejar envío del formulario
        $('#settings-form').on('submit', function (e) {
            e.preventDefault(); 

            var button = $('#settings-button');
            var originalHtml = button.html();
            button.prop("disabled", true).html('<i class="fa-duotone fa-solid fa-spinner fa-spin"></i> <?= translate('saving...'); ?>'); 

            // Limpiar errores previos
            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');

            var prizeRows = $('#roulette-prizes-list .roulette-prize-row').length;
            if (prizeRows < 4 || prizeRows > 8) {
                $('#roulettePrizes-error').text('<?= translate('roulette prizes must be between 4 and 8'); ?>').removeClass('d-none');
                button.prop("disabled", false).html(originalHtml);
                Toastify({
                    text: '<?= translate('roulette prizes must be between 4 and 8'); ?>',
                    duration: 4000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();
                return;
            }

            var hierarchy = validateCommissionHierarchy();
            if (!hierarchy.ok) {
                button.prop("disabled", false).html(originalHtml);
                Toastify({
                    text: '<?= translate('store rate cannot exceed operator rate'); ?>',
                    duration: 4000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();
                if (hierarchy.firstInvalid) {
                    var tabPane = $('#' + hierarchy.firstInvalid).closest('.tab-pane');
                    if (tabPane.length) {
                        $('#' + tabPane.attr('id') + '-tab').tab('show');
                    }
                    $('#' + hierarchy.firstInvalid)[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            $.ajax({
                url: '<?= site_url('home/settingsSubmit') ?>',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $('#settings').modal('hide');

                        Toastify({
                            text: response.message,
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#198754" },
                            stopOnFocus: true
                        }).showToast();

                        // Opcional: recargar la página para reflejar cambios
                        setTimeout(function() {
                            location.reload();
                        }, 1500);

                    } else {
                        if (response.error) {
                            Toastify({
                                text: response.error,
                                duration: 5000,
                                gravity: "top",
                                position: "right",
                                style: { background: "#dc3545" },
                                stopOnFocus: true
                            }).showToast();
                        }

                        if (response.errors) {
                            var firstErrorField = null;
                            $.each(response.errors, function (field, message) {
                                $('#' + field + '-error').text(message).removeClass('d-none');
                                $('#' + field).addClass('is-invalid');
                                
                                if (!firstErrorField) {
                                    firstErrorField = field;
                                    // Cambiar a la pestaña que contiene el error
                                    var tabPane = $('#' + field).closest('.tab-pane');
                                    if (tabPane.length) {
                                        var tabId = tabPane.attr('id');
                                        $('#' + tabId + '-tab').tab('show');
                                    }
                                }
                            });
                            
                            // Hacer scroll al primer campo con error
                            if (firstErrorField) {
                                $('#' + firstErrorField)[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Toastify({
                        text: "<?= translate('there was an error in the request to the server'); ?>",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#dc3545" },
                        stopOnFocus: true
                    }).showToast();
                },
                complete: function() {
                    button.prop("disabled", false).html(originalHtml);
                }
            });
        });

        // Validación condicional para PayPal
        $('#activatePayPal').on('change', function() {
            var isActive = $(this).val() == '1';
            $('#idPayPal, #secretPayPal').prop('required', isActive);
            
            if (!isActive) {
                $('#idPayPal, #secretPayPal').removeClass('is-invalid').val('');
                $('#idPayPal-error, #secretPayPal-error').addClass('d-none');
            }
        });

        // Validar límites financieros
        $('#minimumDeposit, #maximumDeposit').on('input', function() {
            validateLimits('Deposit');
        });

        $('#minimumRetire, #maximumRetire').on('input', function() {
            validateLimits('Retire');
        });

        $('#minimumTransfer, #maximumTransfer').on('input', function() {
            validateLimits('Transfer');
        });



        // Inicializar estado de PayPal
        $('#activatePayPal').trigger('change');
    });

    function validateLimits(type) {
        var minimum = parseFloat($('#minimum' + type).val()) || 0;
        var maximum = parseFloat($('#maximum' + type).val()) || 0;
        
        if (minimum > 0 && maximum > 0 && minimum >= maximum) {
            $('#maximum' + type).addClass('is-invalid');
            $('#maximum' + type + '-error').text('<?= translate('maximum must be greater than minimum'); ?>').removeClass('d-none');
        } else {
            $('#maximum' + type).removeClass('is-invalid');
            $('#maximum' + type + '-error').addClass('d-none');
        }
    }

    function updateBank(bankId) {
        $("#modalBank").load(site_url + 'home/bankGet/' + bankId, function() {
            $('#modalBank').modal('show');
        });
    }

    function bankGet() {
        $("#modalBank").load(site_url + 'home/bankGet', function() {
            $('#modalBank').modal('show');
        });
    }

    function deleteBank(bankId) {
        if (bankId != "") {
            Swal.fire({
                title: '<?= translate('do you want to continue?'); ?>',
                text: '<?= translate('this action will delete the selected data and it cannot be recovered again.'); ?>',
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: '<?= translate('yes, delete!'); ?>',
                cancelButtonText: '<?= translate('cancel'); ?>',
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-danger'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= site_url('home/deleteBank') ?>',
                        method: 'POST',
                        data: {
                            bank_id: bankId,
                            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $('#bank-row-' + bankId).remove();
                                $('#bank option[value="' + bankId + '"]').remove();
                                
                                if ($('#settings-list tr').length === 0) {
                                    $('#settings-list').append('<tr id="not-list"><td colspan="3" class="text-center"><?= translate('no data available'); ?></td></tr>');
                                }

                                Toastify({
                                    text: response.message,
                                    duration: 3000,
                                    gravity: "top",
                                    position: "right",
                                    style: { background: "#198754" },
                                    stopOnFocus: true
                                }).showToast();
                            } else {
                                Toastify({
                                    text: response.error,
                                    duration: 3000,
                                    gravity: "top",
                                    position: "right",
                                    style: { background: "#dc3545" },
                                    stopOnFocus: true
                                }).showToast();
                            }
                        },
                        error: function() {
                            Toastify({
                                text: "<?= translate('there was an error in the request to the server'); ?>",
                                duration: 3000,
                                gravity: "top",
                                position: "right",
                                style: { background: "#dc3545" },
                                stopOnFocus: true
                            }).showToast();
                        }
                    });
                }
            });
        }
    }

    function previewlogoImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('logoImage');
            output.src = reader.result;
            document.getElementById('logo_image_input').value = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>
