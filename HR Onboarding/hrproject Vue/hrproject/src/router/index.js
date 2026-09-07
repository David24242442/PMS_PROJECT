import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import AppInterface from '../views/AppInterface.vue'
import NProgress from 'nprogress'


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
      path: '/test',
      name: 'test',
      component: () => import('@/views/TestView.vue'),
      meta: {  fullname: 'Test' },
    },
    
    {
      path: '/appinterface',
      name: 'appinterface',
      component: AppInterface,
      meta: { requiresAuth: true, fullname: 'App' },
      children: [
        {
          path: '/dashboard',
          name: 'dashboard',
          meta: { fullname: 'Dashboard', roles: [4] },
          component: () => import('@/views/app/DashboardView.vue')
        },
        {
          path: '/onboarding',
          name: 'onboarding',
          meta: { fullname: 'Employee Onboarding', roles: [2, 4] },
          component: () => import('@/views/app/OnbaordingView.vue')
        },
        {
          path: '/employees',
          name: 'employees',
          meta: { fullname: 'Employees', roles: [2, 4] },
          component: () => import('@/views/app/EmployeesView.vue')
        },
        {
          path: '/employee/:empid',
          name: 'employee',
          meta: { fullname: 'Employee details', roles: [2, 4] },
          component: () => import('@/views/app/EmployeeView.vue')
        },
        {
          path: '/users',
          name: 'users',
          meta: { fullname: 'Users', roles: [4] },
          component: () => import('@/views/app/UsersView.vue')
        },
        {
          path: '/profile',
          name: 'profile',
          meta: { fullname: 'Profile', roles: [1, 2, 3, 4] },
          component: () => import('@/views/app/ProfileView.vue')
        },
        // PMS Routes
        {
          path: '/pms/dashboard',
          name: 'pms-dashboard',
          meta: { fullname: 'Performance Dashboard', roles: [3, 4] },
          component: () => import('@/views/app/pms/DashboardView.vue')
        },
        {
          path: '/pms/goals',
          name: 'pms-goals',
          meta: { fullname: 'My Goals', roles: [3, 4] },
          component: () => import('@/views/app/pms/GoalsView.vue')
        },
        {
          path: '/pms/review',
          name: 'pms-review',
          meta: { fullname: 'Performance Review', roles: [3, 4] },
          component: () => import('@/views/app/pms/ReviewView.vue')
        },
        {
          path: '/pms/appraisal',
          name: 'pms-appraisal',
          meta: { fullname: 'Performance Appraisal', roles: [3, 4] },
          component: () => import('@/views/app/pms/AppraisalView.vue')
        },
        {
          path: '/pms/leaderboard',
          name: 'pms-leaderboard',
          meta: { fullname: 'Employee Leaderboard', roles: [3, 4] },
          component: () => import('@/views/app/pms/LeaderboardView.vue')
        },
        {
          path: '/pms/employee-master',
          name: 'pms-employee-master',
          meta: { fullname: 'Employee Master', roles: [4] },
          component: () => import('@/views/app/pms/EmployeeMasterView.vue')
        },
        // HR Admin Routes for PMS
        {
          path: '/hr/submissions',
          name: 'hr-submissions',
          meta: { fullname: 'Appraisal Submissions', roles: [4] },
          component: () => import('@/views/app/hr/SubmissionsView.vue')
        }
      ]
    },
    {
      path: '/:catchAll(.*)*',
      redirect: '/pms/goals'
    }
  ]
})

const parsePermissions = (perms) => {
  if (!perms) return [];
  if (Array.isArray(perms)) return perms;
  if (typeof perms === 'string') {
    try {
      const parsed = JSON.parse(perms);
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      return [];
    }
  }
  return [];
};

router.beforeEach((to, from, next) => {
  const user = JSON.parse(localStorage.getItem('hrproject_user'))
  
  if (to.meta.requiresAuth === false && user) {
    // Dynamic Redirect Logic for already logged-in users visiting login
    const userRole = user.attributes ? user.attributes.position_id : user.position_id;
    const permissions = parsePermissions(user.permissions);
    
    let redirectUrl = '/pms/goals';
    if (user.admin || userRole === 4 || user.is_manager) {
        redirectUrl = '/pms/goals';
    } else if (permissions.includes('/pms/goals')) {
        redirectUrl = '/pms/goals';
    } else if (permissions.includes('/pms/dashboard')) {
        redirectUrl = '/pms/dashboard';
    } else if (permissions.length > 0 && typeof permissions[0] === 'string' && permissions[0].startsWith('/') && permissions[0].length > 1) {
        redirectUrl = permissions[0];
    } else {
        redirectUrl = '/pms/goals';
    }
    
    next(redirectUrl);
    return;
  }

  if (to.meta.requiresAuth && !user) {
    next('/')
    document.title = to.meta.fullname;
    return;
  }

  // Dynamic Permissions Validation Guard
  if (user) {
    const userRole = user.attributes ? user.attributes.position_id : user.position_id; 
    const permissions = parsePermissions(user.permissions);
    const isManager = !!(user.admin || user.is_manager || userRole === 4 || userRole === 3 || user.position_id === 3 || user.position_id === 4);
    
    // HR Head (4) or Admin has full access to everything
    if (userRole === 4 || user.admin) {
       next()
       if (to.meta.fullname) document.title = to.meta.fullname;
       return;
    }

    // Core PMS routes accessible to all authenticated staff / employees
    const staffPmsRoutes = ['/pms/goals', '/pms/appraisal', '/profile'];
    if (staffPmsRoutes.includes(to.path)) {
       next()
       if (to.meta.fullname) document.title = to.meta.fullname;
       return;
    }

    // Manager PMS routes
    const managerPmsRoutes = ['/pms/dashboard', '/pms/review', '/pms/leaderboard'];
    if (managerPmsRoutes.includes(to.path)) {
       if (isManager || permissions.includes(to.path)) {
          next()
          if (to.meta.fullname) document.title = to.meta.fullname;
          return;
       } else {
          next('/pms/goals')
          return;
       }
    }

    // Restricted Administrative / HR routes
    const adminRoutes = [
      '/dashboard', '/onboarding', '/employees', 
      '/users', '/pms/employee-master', '/hr/submissions'
    ];

    if (adminRoutes.includes(to.path) || to.path.startsWith('/employee/')) {
       let checkPath = to.path;
       if (to.path.startsWith('/employee/')) checkPath = '/employees';

       if (!permissions.includes(checkPath)) {
          next('/pms/goals')
          return;
       }
    }
  }

  // Fallback next
  next()
  if (to.meta.fullname) {
    document.title = to.meta.fullname;
  }
})

router.beforeEach((To,From, next) => {
  NProgress.start()       
  next()    
}) 
router.afterEach(() => {  
  NProgress.done()    
}) 

export default router
