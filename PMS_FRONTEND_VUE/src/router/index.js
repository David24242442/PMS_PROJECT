import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import AppInterface from '../views/AppInterface.vue'
import NProgress from 'nprogress'
import Swal from 'sweetalert2'


const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: LoginView,
      meta: { requiresAuth: false, fullname: 'Login' },
    },

    {
      path: '/appinterface',
      name: 'appinterface',
      redirect: '/app/pms/goals',
      component: () => import('../views/AppInterface.vue'), // Changed to lazy load
      meta: { requiresAuth: true, fullname: 'App' },
      children: [
        {
          path: '',
          redirect: '/app/pms/goals'
        },
        {
          path: '/dashboard',
          redirect: '/app/pms/goals'
        },
        // --- PMS Routes ---
        {
          path: '/app/pms/dashboard',
          alias: ['/pms/dashboard'],
          name: 'pms-dashboard',
          meta: { fullname: 'PMS Dashboard' },
          component: () => import('../views/app/pms/DashboardView.vue')
        },
        {
          path: '/app/pms/goals',
          alias: ['/pms/goals', '/goals'],
          name: 'pms-goals',
          meta: { fullname: 'PMS Goals' },
          component: () => import('../views/app/pms/GoalsView.vue')
        },
        {
          path: '/app/pms/appraisal',
          alias: ['/pms/appraisal', '/appraisal'],
          name: 'pms-appraisal',
          meta: { fullname: 'PMS Appraisal' },
          component: () => import('../views/app/pms/AppraisalView.vue')
        },
        {
          path: '/app/pms/review',
          alias: ['/pms/review', '/review'],
          name: 'pms-review',
          meta: { fullname: 'PMS Review' },
          component: () => import('../views/app/pms/ReviewView.vue')
        },
        // --- Admin Routes ---
        {
          path: '/app/admin/users',
          name: 'admin-users-manage',
          meta: { fullname: 'Manage Users' },
          component: () => import('../views/app/admin/UsersManageView.vue')
        },
        {
          path: '/app/admin/upload',
          name: 'admin-upload',
          meta: { fullname: 'Upload Data' },
          component: () => import('../views/app/admin/UploadView.vue')
        },
        // --- HR Routes ---
        {
          path: '/app/hr/submissions',
          alias: ['/hr/submissions'],
          name: 'hr-submissions',
          meta: { fullname: 'HR Submissions' },
          component: () => import('../views/app/hr/SubmissionsView.vue')
        },
        {
          path: '/app/hr/reports',
          alias: ['/hr/reports'],
          name: 'hr-reports',
          meta: { fullname: 'HR Reports' },
          component: () => import('../views/app/hr/ReportsView.vue')
        },
        {
          path: '/users',
          name: 'users',
          meta: { fullname: 'Users' },
          component: () => import('../views/app/UsersView.vue')
        },
        {
          path: '/profile',
          name: 'profile',
          meta: { fullname: 'Profile' },
          component: () => import('../views/app/ProfileView.vue')
        }
      ]
    }
  ]
})

router.beforeEach((to, from, next) => {
  const user = JSON.parse(localStorage.getItem('hrproject_user'))

  // --- Role-Based Access Control ---
  if (['hr-submissions', 'hr-reports'].includes(to.name)) {
    const department = user?.Department || user?.department || '';
    // Check if Department is 'IT HEAD' or 'HR HEAD' (case-sensitive as requested, but safe to check loosely if needed)
    if (department !== 'IT HEAD' && department !== 'HR HEAD') {
      Swal.fire({
        icon: 'error',
        title: 'Access Denied',
        text: 'You do not have permission to access this page.',
        confirmButtonColor: '#d33'
      });
      return next(false);
    }
  }
  // ---------------------------------

  if (to.meta.requiresAuth == false && user) {
    next('/appinterface')
    document.title = to.meta.fullname;
  } else if (to.meta.requiresAuth && !user) {
    router.replace('/')
    document.title = to.meta.fullname;
  } else {
    next()
    document.title = to.meta.fullname;
  }
})

router.beforeEach((To, From, next) => {
  NProgress.start()
  next()
})

router.afterEach(() => {
  NProgress.done()
})

export default router
