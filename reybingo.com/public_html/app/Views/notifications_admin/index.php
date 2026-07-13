<a class="btn btn-small btn-profile" href="<?= site_url('profile'); ?>"><img src="<?= $imagePath ?>" alt="img"></a>

<button type="button" class="btn btn-small btn-wallet btn-wallet-profile" onclick="paymentsGet();">
    <i class="fa-duotone fa-solid fa-wallet"></i>
</button>

<button class="btn btn-small btn-volume hidden" onclick="RemoveVolume();">
    <?php if ($user['sounds'] == 1): ?>
        <i class="fa-duotone fa-solid fa-volume"></i>
    <?php else : ?>
        <i class="fa-duotone fa-solid fa-volume-slash"></i>
    <?php endif; ?>
</button>

<a class="btn btn-small btn-lock hidden" href="<?= site_url('password'); ?>"><i class="fa-duotone fa-solid fa-lock"></i></a>

<button class="btn btn-small btn-sliders" onclick="ViewSliders();"><i class="fa-duotone fa-solid fa-sliders-simple"></i></button>

<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>"><i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i></a>

<button class="btn btn-small btn-statistics" onclick="statisticsView();"><i class="fa-duotone fa-chart-column"></i></button>

<button class="btn btn-small btn-game" onclick="awardsGameGet();"><i class="fa-duotone fa-solid fa-trophy-star"></i></button>

<button class="btn btn-small btn-gear" onclick="settingsGet();"><i class="fa-duotone fa-solid fa-gear"></i></button>

<div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-md-12">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <div class="page-title-right">
                            <a href="<?= base_url('notificationsAdmin/create') ?>" class="btn btn-primary">
                                <i class="mdi mdi-plus-circle me-1"></i> Nueva Notificación
                            </a>
                        </div>
                        <h4 class="page-title">Administración de Notificaciones</h4>
                    </div>
                </div>
            </div>

            <?php if (session()->getFlashdata('success')) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Notification Stats Overview -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="avatar-sm">
                                    <span class="avatar-title bg-primary-lighten rounded-circle text-primary">
                                        <i class="mdi mdi-bell-outline font-24"></i>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <h3 class="text-dark mt-1"><?= $stats['total'] ?? 0 ?></h3>
                                    <p class="text-muted mb-1">Notificaciones Totales</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="avatar-sm">
                                    <span class="avatar-title bg-success-lighten rounded-circle text-success">
                                        <i class="mdi mdi-bell-ring-outline font-24"></i>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <h3 class="text-dark mt-1"><?= $stats['sent'] ?? 0 ?></h3>
                                    <p class="text-muted mb-1">Enviadas Hoy</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="avatar-sm">
                                    <span class="avatar-title bg-info-lighten rounded-circle text-info">
                                        <i class="mdi mdi-calendar-clock font-24"></i>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <h3 class="text-dark mt-1"><?= $stats['scheduled'] ?? 0 ?></h3>
                                    <p class="text-muted mb-1">Programadas</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="avatar-sm">
                                    <span class="avatar-title bg-warning-lighten rounded-circle text-warning">
                                        <i class="mdi mdi-eye-outline font-24"></i>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <h3 class="text-dark mt-1"><?= $stats['read_rate'] ?? '0' ?>%</h3>
                                    <p class="text-muted mb-1">Tasa de Lectura</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title">Acciones Rápidas</h4>
                            <div class="row">
                                <div class="col-md-3">
                                    <a href="<?= base_url('notificationsAdmin/create') ?>" class="btn btn-primary w-100 mb-2">
                                        <i class="mdi mdi-plus-circle me-1"></i> Nueva Notificación
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="<?= base_url('notificationsAdmin/templates') ?>" class="btn btn-info w-100 mb-2">
                                        <i class="mdi mdi-file-document-outline me-1"></i> Plantillas
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="<?= base_url('notificationsAdmin/scheduled') ?>" class="btn btn-success w-100 mb-2">
                                        <i class="mdi mdi-calendar-clock me-1"></i> Programadas
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="<?= base_url('notificationsAdmin/stats') ?>" class="btn btn-warning w-100 mb-2">
                                        <i class="mdi mdi-chart-bar me-1"></i> Estadísticas
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title">Notificaciones Recientes</h4>
                            <p class="text-muted">Listado de las notificaciones más recientes.</p>

                            <ul class="nav nav-tabs nav-bordered mb-3">
                                <li class="nav-item">
                                    <a href="#all-notifications" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                        Todas
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#scheduled-notifications" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        Programadas
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#sent-notifications" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        Enviadas
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#read-notifications" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        Leídas
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane show active" id="all-notifications">
                                    <div class="table-responsive">
                                        <table class="table table-centered table-striped dt-responsive nowrap w-100" id="notifications-table">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Usuario</th>
                                                    <th>Título</th>
                                                    <th>Tipo</th>
                                                    <th>Programada</th>
                                                    <th>Enviada</th>
                                                    <th>Estado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($notifications as $notification) : ?>
                                                    <tr>
                                                        <td><?= $notification['id'] ?></td>
                                                        <td>
                                                            <?php if ($notification['user'] > 0) : ?>
                                                                <a href="<?= base_url('users/view/' . $notification['user']) ?>"><?= $notification['username'] ?? 'Usuario #' . $notification['user'] ?></a>
                                                            <?php else : ?>
                                                                <span class="badge bg-info">Todos</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= $notification['title'] ?></td>
                                                        <td>
                                                            <?php if ($notification['type']) : ?>
                                                                <span class="badge bg-primary"><?= $notification['type'] ?></span>
                                                            <?php else : ?>
                                                                <span class="badge bg-secondary">General</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?= isset($notification['scheduled_at']) ? date('d/m/Y H:i', strtotime($notification['scheduled_at'])) : '-' ?>
                                                        </td>
                                                        <td>
                                                            <?= isset($notification['sent_at']) ? date('d/m/Y H:i', strtotime($notification['sent_at'])) : '-' ?>
                                                        </td>
                                                        <td>
                                                            <?php if (isset($notification['scheduled_at']) && !isset($notification['sent_at'])) : ?>
                                                                <span class="badge bg-info">Programada</span>
                                                            <?php elseif (isset($notification['sent_at']) && $notification['status'] == 0) : ?>
                                                                <span class="badge bg-warning">Enviada</span>
                                                            <?php elseif ($notification['status'] == 1) : ?>
                                                                <span class="badge bg-success">Leída</span>
                                                            <?php else : ?>
                                                                <span class="badge bg-secondary">Pendiente</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group dropdown">
                                                                <a href="javascript:void(0);" class="dropdown-toggle arrow-none btn btn-light btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                                                    <i class="mdi mdi-dots-vertical"></i>
                                                                </a>
                                                                <div class="dropdown-menu dropdown-menu-end">
                                                                    <a class="dropdown-item" href="<?= base_url('notificationsAdmin/view/' . $notification['id']) ?>">
                                                                        <i class="mdi mdi-eye me-1"></i> Ver Detalles
                                                                    </a>
                                                                    <?php if (!isset($notification['sent_at'])) : ?>
                                                                        <a class="dropdown-item" href="<?= base_url('notificationsAdmin/edit/' . $notification['id']) ?>">
                                                                            <i class="mdi mdi-pencil me-1"></i> Editar
                                                                        </a>
                                                                        <a class="dropdown-item" href="<?= base_url('notificationsAdmin/send/' . $notification['id']) ?>">
                                                                            <i class="mdi mdi-send me-1"></i> Enviar Ahora
                                                                        </a>
                                                                    <?php endif; ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item text-danger" href="<?= base_url('notificationsAdmin/delete/' . $notification['id']) ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar esta notificación?');">
                                                                        <i class="mdi mdi-delete me-1"></i> Eliminar
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Other tabs would be similar but with filtered data -->
                                <div class="tab-pane" id="scheduled-notifications">
                                    <!-- Scheduled notifications table -->
                                </div>

                                <div class="tab-pane" id="sent-notifications">
                                    <!-- Sent notifications table -->
                                </div>

                                <div class="tab-pane" id="read-notifications">
                                    <!-- Read notifications table -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Templates Section -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="header-title">Plantillas de Notificación</h4>
                                <a href="<?= base_url('notificationsAdmin/createTemplate') ?>" class="btn btn-sm btn-primary">
                                    <i class="mdi mdi-plus-circle me-1"></i> Nueva Plantilla
                                </a>
                            </div>
                            
                            <div class="row">
                                <?php foreach ($templates as $template) : ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card border">
                                            <div class="card-header bg-light">
                                                <h5 class="card-title mb-0"><?= $template['name'] ?></h5>
                                            </div>
                                            <div class="card-body">
                                                <p><strong>Título:</strong> <?= $template['title_template'] ?></p>
                                                <p class="mb-0"><strong>Mensaje:</strong> <?= substr($template['message_template'], 0, 100) ?>...</p>
                                            </div>
                                            <div class="card-footer">
                                                <div class="btn-group w-100">
                                                    <a href="<?= base_url('notificationsAdmin/viewTemplate/' . $template['id']) ?>" class="btn btn-sm btn-info">
                                                        <i class="mdi mdi-eye"></i>
                                                    </a>
                                                    <a href="<?= base_url('notificationsAdmin/editTemplate/' . $template['id']) ?>" class="btn btn-sm btn-primary">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
                                                    <a href="<?= base_url('notificationsAdmin/useTemplate/' . $template['id']) ?>" class="btn btn-sm btn-success">
                                                        <i class="mdi mdi-send"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>