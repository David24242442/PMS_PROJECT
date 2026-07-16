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
    }
  ]
})

router.beforeEach((to, from, next) => {
  const user = JSON.parse(localStorage.getItem('hrproject_user'))
  
  if (to.meta.requiresAuth === false && user) {
    // Dynamic Redirect Logic for already logged-in users visiting login
    const userRole = user.attributes ? user.attributes.position_id : user.position_id;
    const permissions = user.permissions || [];
    
    let redirectUrl = '/profile';
    if (userRole === 4) {
        redirectUrl = '/pms/goals';
    } else if (permissions.includes('/pms/goals')) {
        redirectUrl = '/pms/goals';
    } else if (permissions.length > 0) {
        redirectUrl = permissions[0];
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
    const permissions = user.permissions || [];
    
    // HR Head (4) has access to everything
    if (userRole === 4) {
       next()
       if (to.meta.fullname) document.title = to.meta.fullname;
       return;
    }

    const restrictedPaths = [
      '/dashboard', '/onboarding', '/employees', 
      '/users', '/pms/dashboard', '/pms/goals', '/pms/review', '/pms/appraisal',
      '/pms/leaderboard', '/pms/employee-master', '/hr/submissions'
    ];

    if (restrictedPaths.includes(to.path) || to.path.startsWith('/employee/')) {
       let checkPath = to.path;
       if (to.path.startsWith('/employee/')) checkPath = '/employees';
       else if (to.path === '/pms/leaderboard') checkPath = '/pms/goals'; // leaderboard shares access with goals

       if (!permissions.includes(checkPath)) {
          next('/profile')
          document.title = 'Profile';
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
