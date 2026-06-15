<div class="row mt-4">
    <div class="col-md-12">
        <h4><?= translate('users management'); ?></h4>
        <?php if (session()->get('group') == 1): ?>
            <a href="<?= site_url('users/exportRequiredFields'); ?>" class="btn btn-sm btn-primary btn-bingo float-end">
                <i class="fa-duotone fa-solid fa-file-arrow-down"></i> Exportar usuarios (campos requeridos)
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-2">
	<div class="row">
	    <div class="col-12 col-md">
	    	<div class="card bingo-bg-primary text-white m-2">
		        <div class="card-body">
		            <h5><?= translate('total users'); ?></h5>
		            <h2><?= number_format($stats['total_users']); ?></h2>
		        </div>
		    </div>
		</div>
	    
	    <div class="col-12 col-md">
	    	<div class="card bingo-bg-success text-white m-2">
		        <div class="card-body">
		            <h5><?= translate('active users'); ?></h5>
		            <h2><?= number_format($stats['active_users']); ?></h2>
		        </div>
		    </div>
		</div>
	    
		<div class="col-12 col-md">
		    <div class="card bingo-bg-danger text-white m-2">
		        <div class="card-body">
		            <h5><?= translate('banned users'); ?></h5>
		            <h2><?= number_format($stats['banned_users']); ?></h2>
		        </div>
		    </div>
		</div>
	</div>
</div>
        
<div class="card mt-3">
	<div class="row">
	    <div class="col-12 col-md">
		    <div class="card bingo-bg-info text-white m-2">
		        <div class="card-body">
		            <h5><?= translate('total wallet'); ?></h5>
		            <h2><?= systemGet('currency'); ?> <?= number_format($stats['total_wallet'], 2); ?></h2>
		        </div>
		    </div>
		</div>

	   	<div class="col-12 col-md">
	   		<div class="card bingo-bg-warning text-white m-2">
		        <div class="card-body">
		            <h5><?= translate('today users'); ?></h5>
		            <h2><?= number_format($stats['today_users']); ?></h2>
		        </div>
		    </div>
		</div>
		    
	    <div class="col-12 col-md">
	    	<div class="card bingo-bg-secondary text-white m-2">
		        <div class="card-body">
		            <h5><?= translate('avg wallet'); ?></h5>
		            <h2><?= systemGet('currency'); ?> <?= number_format($stats['avg_wallet'], 2); ?></h2>
		        </div>
		    </div>
		</div>
	</div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="collapse show">
                <div class="card-body p-3">
	        		<button type="button" class="btn btn-small btn-primary btn-modal-add text-white float-end mt-4 btn-add-new" onclick="addUser();"><i class="fa-duotone fa-solid fa-plus"></i></button>
				    <div class="row g-2">
					    <div class="col-md-4">
					    	<label class="form-label small"><?= translate('user'); ?></label>
					        <input type="text" class="form-control form-control-lg form-bingo" id="searchUsers" placeholder="<?= translate('search users'); ?>..." value="<?= esc($search); ?>">
					    </div>
					    <div class="col-md-4">
					    	<label class="form-label small"><?= translate('status'); ?></label>
					        <select class="form-control form-control-lg form-bingo" id="statusFilter" onchange="filterUsers()">
					            <option value="all" <?= $status == 'all' ? 'selected' : ''; ?>><?= translate('all status'); ?></option>
					            <option value="1" <?= $status == '1' ? 'selected' : ''; ?>><?= translate('active'); ?></option>
					            <option value="0" <?= $status == '0' ? 'selected' : ''; ?>><?= translate('banned'); ?></option>
					        </select>
					    </div>
					    <div class="col-md-4">
					    	<label class="form-label small"><?= translate('group'); ?></label>
					        <select class="form-control form-control-lg form-bingo" id="groupFilter" onchange="filterUsers()">
					            <option value="all" <?= $group == 'all' ? 'selected' : ''; ?>><?= translate('all groups'); ?></option>
					            <option value="1" <?= $group == '1' ? 'selected' : ''; ?>><?= translate('admin'); ?></option>
					            <option value="0" <?= $group == '0' ? 'selected' : ''; ?>><?= translate('player'); ?></option>
					        </select>
					    </div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="row mt-3">
	<div class="col-md-12">
		<div class="card">
	        <div class="card-header pt-3">
	            <h5><?= translate('users list'); ?></h5>
	        </div>
	        <div class="card-body">
	            <div class="table-responsive">
	                <table class="table table-striped mb-0">
	                    <thead>
	                        <tr>
	                            <th><?= translate('user information'); ?></th>
	                            <th><?= translate('group'); ?></th>
	                            <th><?= translate('wallet'); ?></th>
	                            <th><?= translate('activity'); ?></th>
	                            <th><?= translate('status'); ?></th>
	                            <th><?= translate('registered'); ?></th>
	                            <th class="text-center"><?= translate('actions'); ?></th>
	                        </tr>
	                    </thead>
	                    <tbody>
	                        <?php if (!empty($users)): ?>
	                            <?php foreach ($users as $user): ?>
	                            <tr>
									<td>
									    <div class="d-flex align-items-center">
									        <?php if ($user['image']): ?>
									            <img src="<?= site_url('uploads/users/' . $user['image']); ?>" class="rounded-circle me-3" width="50" height="50">
									        <?php else: ?>
									            <div class="bingo-bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; text-transform: uppercase;">
									                <span class="text-white"><?= strtoupper(substr($user['firstname'], 0, 1)); ?></span>
									            </div>
									        <?php endif; ?>
									        <div>
									            <strong><?= esc($user['firstname'] . ' ' . $user['lastname']); ?></strong><br>
									            <small class="text-muted">
									            	@<?= esc($user['username']); ?>
								            		<?php if ($user['phone']): ?>
								            			- <i class="fa-duotone fa-phone text-muted"></i> <?= esc($user['phone']); ?>
								                    <?php endif; ?>
									            </small><br>
									            <div class="mt-1">
									                <small>
									                    <i class="fa-duotone fa-envelope text-muted"></i> <?= esc($user['email']); ?>
									                </small>
									            </div>
									        </div>
									    </div>
									</td>
	                                <td>
	                                    <span class="badge <?= $user['group'] == 1 ? 'bg-warning' : 'bg-info'; ?>">
	                                        <?= $user['group'] == 1 ? translate('admin') : translate('player'); ?>
	                                    </span>
	                                </td>
	                                <td>
	                                    <strong class="text-success"><?= systemGet('currency'); ?> <?= number_format($user['wallet'], 2); ?></strong>
	                                </td>
	                                <td>
	                                    <small>
	                                        <?= translate('cartons'); ?>: <strong><?= number_format($user['total_cartons']); ?></strong><br>
	                                        <?= translate('deposits'); ?>: <strong class="text-success"><?= systemGet('currency'); ?> <?= number_format($user['total_deposits'], 2); ?></strong><br>
	                                        <?php if ($user['last_activity']): ?>
	                                            <span class="text-muted"><?= date('d/m/Y H:i', strtotime($user['last_activity'])); ?></span>
	                                        <?php else: ?>
	                                            <span class="text-muted"><?= translate('no activity'); ?></span>
	                                        <?php endif; ?>
	                                    </small>
	                                </td>
	                                <td>
	                                    <span class="badge <?= $user['status'] == 1 ? 'bg-success' : 'bg-danger'; ?>">
	                                        <?= $user['status'] == 1 ? translate('active') : translate('banned'); ?>
	                                    </span>
	                                </td>
	                                <td>
	                                    <small><?= date('d/m/Y', strtotime($user['created_at'])); ?></small>
	                                </td>
	                                <td class="text-center">
	                                    <div class="btn-group btn-group-sm" role="group">
	                                        <button type="button" class="btn btn-info" onclick="viewUser(<?= $user['id']; ?>)" title="<?= translate('view details'); ?>">
	                                            <i class="fa-duotone fa-eye fs-5"></i>
	                                        </button>
	                                        <button type="button" class="btn btn-primary" onclick="updateUser(<?= $user['id']; ?>)" title="<?= translate('edit'); ?>">
	                                            <i class="fa-duotone fa-edit fs-5"></i>
	                                        </button>
	                                        <button type="button" class="btn btn-<?= $user['status'] == 1 ? 'warning' : 'success'; ?> p-2" onclick="banUser(<?= $user['id']; ?>, <?= $user['status'] == 1 ? 0 : 1; ?>)" title="<?= $user['status'] == 1 ? translate('ban') : translate('unban'); ?>">
	                                            <i class="fa-duotone fa-<?= $user['status'] == 1 ? 'ban' : 'check'; ?> fs-5"></i>
	                                        </button>
	                                        <button type="button" class="btn btn-danger" onclick="deleteUser(<?= $user['id']; ?>)" title="<?= translate('delete'); ?>">
	                                            <i class="fa-duotone fa-trash fs-5"></i>
	                                        </button>
	                                    </div>
	                                </td>
	                            </tr>
	                            <?php endforeach; ?>
	                        <?php else: ?>
	                            <tr>
	                                <td colspan="8" class="text-center"><?= translate('no users found'); ?>
	                                </td>
	                            </tr>
	                        <?php endif; ?>
	                    </tbody>
	                </table>
	            </div>
	            
				<?php 
					$showPagination = false;
					$totalPages = 1;
					$currentPage = 1;
					$totalRecords = count($users);

					if (isset($pager) && $pager) {
					    if (method_exists($pager, 'getLastPage')) {
					        $totalPages = $pager->getLastPage();
					        $showPagination = $totalPages > 1;
					    } elseif (method_exists($pager, 'getPageCount')) {
					        $totalPages = $pager->getPageCount();
					        $showPagination = $totalPages > 1;
					    }
					    
					    if (method_exists($pager, 'getCurrentPage')) {
					        $currentPage = $pager->getCurrentPage();
					    }
					    
					    if (method_exists($pager, 'getTotal')) {
					        $totalRecords = $pager->getTotal();
					    }
					}
				?>

				<?php if ($showPagination): ?>
					<div class="row mt-4">
				        <div class="col-12 col-md text-center mt-2 mb-sm-3">
				            <span class="text-muted">
				                <?= translate('showing'); ?> 
				                <?= ($currentPage - 1) * $per_page + 1; ?> - 
				                <?= min($currentPage * $per_page, $totalRecords); ?> 
				                <?= translate('of'); ?> <?= number_format($totalRecords); ?> <?= translate('users'); ?>
				            </span>
				        </div>
				        <div class="col-12 col-md text-center">
					        <nav class="d-flex justify-content-center align-items-center">
					            <!-- Paginación manual -->
					            <ul class="pagination mb-0">
					                <?php if ($currentPage > 1): ?>
					                    <li class="page-item">
					                        <a class="page-link" href="javascript:void(0)" onclick="statisticsGet('players', {page: <?= $currentPage - 1; ?>})">
					                            «
					                        </a>
					                    </li>
					                <?php endif; ?>
					                
					                <?php 
					                $startPage = max(1, $currentPage - 2);
					                $endPage = min($totalPages, $currentPage + 2);
					                
					                if ($startPage > 1): ?>
					                    <li class="page-item">
					                        <a class="page-link" href="javascript:void(0)" onclick="statisticsGet('players', {page: 1})">1</a>
					                    </li>
					                    <?php if ($startPage > 2): ?>
					                        <li class="page-item disabled">
					                            <span class="page-link">...</span>
					                        </li>
					                    <?php endif; ?>
					                <?php endif; ?>
					                
					                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
					                    <li class="page-item <?= $i == $currentPage ? 'active' : ''; ?>">
					                        <a class="page-link" href="javascript:void(0)" onclick="statisticsGet('players', {page: <?= $i; ?>})">
					                            <?= $i; ?>
					                        </a>
					                    </li>
					                <?php endfor; ?>
					                
					                <?php if ($endPage < $totalPages): ?>
					                    <?php if ($endPage < $totalPages - 1): ?>
					                        <li class="page-item disabled">
					                            <span class="page-link">...</span>
					                        </li>
					                    <?php endif; ?>
					                    <li class="page-item">
					                        <a class="page-link" href="javascript:void(0)" onclick="statisticsGet('players', {page: <?= $totalPages; ?>})"><?= $totalPages; ?></a>
					                    </li>
					                <?php endif; ?>
					                
					                <?php if ($currentPage < $totalPages): ?>
					                    <li class="page-item">
					                        <a class="page-link" href="javascript:void(0)" onclick="statisticsGet('players', {page: <?= $currentPage + 1; ?>})">
					                             »
					                        </a>
					                    </li>
					                <?php endif; ?>
					            </ul>
					        </nav>
					    </div>
				    </div>
				<?php endif; ?>
	        </div>
	    </div>
	</div>
</div>

<script type="text/javascript">
	function searchUsers() {
	    var search = $('#searchUsers').val();
	    var status = $('#statusFilter').val();
	    var group = $('#groupFilter').val();
	    
	    // Recargar el tab con los nuevos filtros
	    statisticsGet('players', {
	        search: search,
	        status: status,
	        group: group,
	        page: 1
	    });
	}

	function filterUsers() {
	    searchUsers(); // Usar la misma función
	}

	// Permitir búsqueda con Enter
	$('#searchUsers').on('keypress', function(e) {
	    if (e.which == 13) {
	        searchUsers();
	    }
	});

	function addUser() {
	    $("#modalUser").load('<?= site_url('users/add'); ?>', function() {
	        $('#modalUser').modal('show');
	    });
	}

	function updateUser(userId) {
	    $("#modalUser").load('<?= site_url('users/add/'); ?>' + userId, function() {
	        $('#modalUser').modal('show');
	    });
	}

	function deleteUser(userId) {
	    if (userId != "") {
	        Swal.fire({
	            title: '<?= translate('do you want to continue?'); ?>',
	            text: '<?= translate('this action will delete the selected user and it cannot be recovered again.'); ?>',
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
	                    url: '<?= site_url('users/deleteUser') ?>',
	                    method: 'POST',
	                    data: {
	                        user_id: userId,
	                        '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
	                    },
	                    dataType: 'json',
	                    success: function(response) {
	                        if (response.success) {
	                            statisticsGet('players'); // Recargar el tab
	                            
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

	function banUser(userId, status) {
	    const action = status == 0 ? '<?= translate('ban'); ?>' : '<?= translate('unban'); ?>';
	    const message = status == 0 ? '<?= translate('this will ban the user'); ?>' : '<?= translate('this will unban the user'); ?>';
	    
	    Swal.fire({
	        title: '<?= translate('do you want to continue?'); ?>',
	        text: message,
	        icon: 'warning',
	        showCancelButton: true,
	        confirmButtonText: '<?= translate('yes'); ?>, ' + action + '!',
	        cancelButtonText: '<?= translate('cancel'); ?>',
	        customClass: {
	            confirmButton: 'btn btn-primary',
	            cancelButton: 'btn btn-danger'
	        }
	    }).then((result) => {
	        if (result.isConfirmed) {
	            $.ajax({
	                url: '<?= site_url('users/banUser') ?>',
	                method: 'POST',
	                data: {
	                    user_id: userId,
	                    status: status,
	                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
	                },
	                dataType: 'json',
	                success: function(response) {
	                    if (response.success) {
	                        statisticsGet('players'); // Recargar el tab
	                        
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

	function viewUser(userId) {
	    $.ajax({
	        url: '<?= site_url('users/getUserDetails/'); ?>' + userId,
	        method: 'GET',
	        dataType: 'json',
	        success: function(response) {
	            if (response.success) {
	                const user = response.user;
	                const stats = response.stats;
	                
	                let content = `
	                    <div class="row">
	                        <div class="col-md-4 text-center">
	                            ${user.image ? 
	                                `<img src="<?= site_url('uploads/users/'); ?>${user.image}" class="rounded-circle mb-3" width="120" height="120">` :
	                                `<div class="bingo-bg-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
	                                    <span class="text-white" style="font-size: 2rem;">${user.firstname.charAt(0).toUpperCase()}</span>
	                                </div>`
	                            }
	                            <h5>${user.firstname} ${user.lastname}</h5>
	                            <p class="text-muted">@${user.username}</p>
	                            <span class="badge ${user.status == 1 ? 'bg-success' : 'bg-danger'}">${user.status == 1 ? '<?= translate('active'); ?>' : '<?= translate('banned'); ?>'}</span>
	                            <h6 class="mt-3"><?= translate('banking information'); ?></h6>
	                            <strong><?= translate('bank'); ?>:</strong><br>
	                            <p class="text-muted mb-0">${user.bank || '<?= translate('not provided'); ?>'}</p>
	                            <strong><?= translate('account'); ?>:</strong><br>
	                            <p class="text-muted mb-0">${user.account || '<?= translate('not provided'); ?>'}</p>

	                        </div>
	                        <div class="col-md-8">
	                            <h6><?= translate('personal information'); ?></h6>
	                            <table class="table table-sm">
	                                <tr><td><strong><?= translate('code'); ?>:</strong></td><td>${user.code}</td></tr>
	                                <tr><td><strong><?= translate('email'); ?>:</strong></td><td>${user.email}</td></tr>
	                                <tr><td><strong><?= translate('phone'); ?>:</strong></td><td>${user.phone || '<?= translate('not provided'); ?>'}</td></tr>
	                                <tr><td><strong><?= translate('document'); ?>:</strong></td><td>${user.document || '<?= translate('not provided'); ?>'}</td></tr>
	                                <tr><td><strong><?= translate('group'); ?>:</strong></td><td>${user.group == 1 ? '<?= translate('admin'); ?>' : '<?= translate('player'); ?>'}</td></tr>
	                                <tr><td><strong><?= translate('wallet'); ?>:</strong></td><td><span class="badge bg-success p-2 fs-6"><?= systemGet('currency'); ?> ${parseFloat(user.wallet).toFixed(2)}<span></td></tr>
	                                <tr><td><strong><?= translate('registered'); ?>:</strong></td><td>${new Date(user.created_at).toLocaleString()}</td></tr>
	                                <tr><td><strong><?= translate('last update'); ?>:</strong></td><td>${new Date(user.updated_at).toLocaleString()}</td></tr>
	                            </table>
	                            <h6 class="mt-3"><?= translate('activity statistics'); ?></h6>
	                            <table class="table table-sm">
	                                <tr><td><strong><?= translate('total cartons'); ?>:</strong></td><td><span class="badge bg-primary p-2 fs-6">${stats.total_cartons}</span></td></tr>
	                                <tr><td><strong><?= translate('total deposits'); ?>:</strong></td><td><span class="badge bg-success p-2 fs-6"><?= systemGet('currency'); ?> ${parseFloat(stats.total_deposits).toFixed(2)}</span></td></tr>
	                                <tr><td><strong><?= translate('total retires'); ?>:</strong></td><td><span class="badge bg-danger p-2 fs-6"><?= systemGet('currency'); ?> ${parseFloat(stats.total_retires).toFixed(2)}</span></td></tr>
	                                <tr><td><strong><?= translate('total roulettes'); ?>:</strong></td><td><span class="badge bg-info p-2 fs-6"><?= systemGet('currency'); ?> ${parseFloat(stats.total_roulettes).toFixed(2)}</span></td></tr>
	                                <tr><td><strong><?= translate('last activity'); ?>:</strong></td><td>${stats.last_activity ? new Date(stats.last_activity).toLocaleString() : '<?= translate('no activity'); ?>'}</td></tr>
	                            </table>
	                        </div>
	                    </div>
	                `;
	                
	                $('#userDetailsContent').html(content);
	                $('#modalUserDetails').modal('show');
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
</script>
