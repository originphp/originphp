<?php

namespace App\Controller;

class BookmarksController extends AppController
{
    public $paginate = [
      'limit' => 20,
    ];

    public function index()
    {
        $this->set('bookmarks', $this->paginate('Bookmark', [
            'with' => ['User']
        ]));
        deprecationWarning('This is a test');
    }

    public function view($id = null)
    {
        $bookmark = $this->Bookmark->get($id, [
            'with'=>['User','Tag']
            ]);
   
        $this->set('bookmark', $bookmark);
    }

    public function add()
    {
        $bookmark = $this->Bookmark->newEntity();

        if ($this->request->is(['post'])) {
            $bookmark = $this->Bookmark->newEntity($this->request->data);
            $bookmark->user_id = $this->Auth->user('id');
            if ($this->Bookmark->save($bookmark)) {
                $this->Flash->success(__('Your bookmark has been created.'));

                return $this->redirect(['action' => 'view', $this->Bookmark->id]);
            }
            $this->Flash->error(__('Your bookmark could not be saved'));
        }

        $users = $this->Bookmark->User->find('list');
        $this->set(compact('users'));
        $this->set('bookmark', $bookmark);
        $this->set('categories', $this->Bookmark->categories);
    }

    public function edit($id = null)
    {
        $bookmark = $this->Bookmark->get($id, [
            'with' => ['Tag']
        ]);
    
        if ($this->request->is(['post', 'put'])) {
            $bookmark = $this->Bookmark->patchEntity($bookmark, $this->request->data);

            if ($this->Bookmark->save($bookmark)) {
                $this->Flash->success(__('Your bookmark has been updated.'));

                return $this->redirect(['action' => 'view', $this->Bookmark->id]);
            }

            $this->Flash->error(__('Your bookmark could not be saved'));
        }

        $users = $this->Bookmark->User->find('list');
        $this->set(compact('users'));
        $this->set('bookmark', $bookmark);
        $this->set('categories', $this->Bookmark->categories);
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $bookmark = $this->Bookmark->get($id);

        if ($this->Bookmark->delete($bookmark->id)) {
            $this->Flash->success(__('The bookmark %d hasbeen deleted.', $bookmark->id));
        } else {
            $this->Flash->error(__('The bookmark could not be deleted.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
