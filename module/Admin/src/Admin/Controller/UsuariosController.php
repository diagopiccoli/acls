<?php

namespace Admin\Controller;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;
use \Admin\Form\Usuario as UsuarioForm;
use \Admin\Entity\Usuario as Usuario;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;

/**
 * Controlador que gerencia os usuários
 *
 * @category Admin
 * @package Controller
 * @author  Cezar Junior de Souza <cezar08@unochapeco.edu.br>
 */
class UsuariosController extends AbstractActionController
{

    /**
     * Exibe os usuários
     * @return void
     */
    public function indexAction()
    {
        $em =  $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        $query = $em
        ->createQuery('SELECT Usuario FROM \Admin\Entity\Usuario Usuario');
        

        $paginator = new Paginator(
            new DoctrinePaginator(new ORMPaginator($query))
        );


        $paginator
        ->setCurrentPageNumber($this->params()->fromRoute('page'))
        ->setItemCountPerPage(2);

        return new ViewModel(
            array(
                'usuarios' => $paginator
            )
        );
    }

    /**
     * Cria ou edita um usuário
     * @return void
     */
    public function saveAction()
    {
        $em =  $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        $form = new UsuarioForm($em);
        $request = $this->getRequest();

        if ($request->isPost()) {
            $usuario = new Usuario();
            $values = $request->getPost();
            $form->setInputFilter($usuario->getInputFilter());
            $form->setData($values);
			
            if ($form->isValid()) {				
                $values = $form->getData();

                if ( (int) $values['id'] > 0)
                    $usuario = $em->find('\Admin\Entity\Usuario', $values['id']);

                $usuario->setNome($values['nome']);
                $usuario->setEmail($values['email']);
                $usuario->setSenha($values['senha']);
                $usuario->setSobrenome($values['sobrenome']);
                $usuario->setRole($values['role']);
                $sexo = $em->find('\Admin\Entity\Sexo', $values['sexo']);
                $usuario->setSexo($sexo);
		$usuario->getInteresses()->clear();

                foreach ($values['interesses'] as $interesse) {
                    $interesse = $em->find('\Admin\Entity\Interesse', $interesse);
                    $usuario->getInteresses()->add($interesse);
                }

                $em->persist($usuario);

                try {
                    $em->flush();
                    $this->flashMessenger()->addSuccessMessage('Usuário armazenado com sucesso');
                } catch (\Exception $e) {
                    $this->flashMessenger()->addErrorMessage('Erro ao armazenar usuário');
                }

                return $this->redirect()->toUrl('/admin/usuarios');
            }
        }

        $id = $this->params()->fromRoute('id', 0);

        if ((int) $id > 0) {
            $usuario = $em->find('\Admin\Entity\Usuario', $id);
            $form->bind($usuario);
        }

        return new ViewModel(
            array('form' => $form)
        );
    }

	public function interessesAction()
	{
		$id = $this->params()->fromRoute('id',0);
		
		if ($id > 0) {
			$em =  $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
			$usuario = $em->find('\Admin\Entity\Usuario', $id);

			return new ViewModel(
				array(
					'usuario' => $usuario				
				)
			);
		}

		return $this->redirect()->toUrl('/admin/usuarios');
	}
    

    /**
     * Exclui um usuário
     * @return void
     */
    public function deleteAction()
    {
        $id = $this->params()->fromRoute('id', 0);
        $em =  $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');

        if ($id > 0) {
            $usuario = $em->find('\Admin\Entity\Usuario', $id);
            $em->remove($usuario);

            try {
                $em->flush();
                $this->flashMessenger()->addSuccessMessage('Usuário excluído com sucesso');
            } catch (\Exception $e) {
                $this->flashMessenger()->addErrorMessage('Erro ao excluir usuário');
            }
        }

        return $this->redirect()->toUrl('/admin/usuarios');
    }

}
