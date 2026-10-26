<div id="content">
<!--start-top-serch-->
  <div id="content-header">
   <div></div>
      <div id="breadcrumb" role="navigation" aria-label="Localização no sistema">
        <a href="<?= base_url() ?>" class="tip-bottom">Início</a>
        <?php
        $breadcrumbModule = $this->uri->segment(1);
        $breadcrumbAction = $this->uri->segment(2);
        $breadcrumbLabels = ['operacoes' => 'Operações', 'comunicacao' => 'Comunicação', 'os' => 'Ordens de serviço', 'permissoes' => 'Permissões', 'usuarios' => 'Usuários', 'servicos' => 'Serviços', 'relatorios' => 'Relatórios', 'relatoriotecnico' => 'Relatório técnico', 'adicionar' => 'Cadastrar', 'editar' => 'Editar', 'visualizar' => 'Visualizar', 'gerenciar' => 'Listagem', 'configurar' => 'Configurações', 'lancamentos' => 'Lançamentos'];
        if ($breadcrumbModule) { ?>
            <a href="<?= html_escape(site_url($breadcrumbModule)) ?>" <?= ! $breadcrumbAction ? 'aria-current="page"' : '' ?>><?= html_escape($breadcrumbLabels[$breadcrumbModule] ?? ucfirst($breadcrumbModule)) ?></a>
        <?php }
        if ($breadcrumbModule && $breadcrumbAction) { ?>
            <a href="<?= html_escape(current_url()) ?>" class="current" aria-current="page"><?= html_escape($breadcrumbLabels[$breadcrumbAction] ?? ucfirst($breadcrumbAction)) ?></a>
        <?php } ?>
      </div>
    </div>
    <div class="container-flu">
      <div class="row-fluid">
        <div class="span12">
          <?php if ($var = $this->session->flashdata('success')): ?><script>swal("Sucesso!", "<?php echo str_replace('"', '', $var); ?>", "success");</script><?php endif; ?>
          <?php if ($var = $this->session->flashdata('error')): ?><script>swal("Falha!", "<?php echo str_replace('"', '', $var); ?>", "error");</script><?php endif; ?>
          <?php if (isset($view)) {
              echo $this->load->view($view, null, true);
          } ?>
        </div>
      </div>
    </div>
  </div>
