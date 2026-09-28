<template>
  <div class="min-h-screen bg-slate-100 text-slate-800 font-sans selection:bg-blue-100 selection:text-blue-900 pb-28 sm:pb-16">
    
    <!-- ======================================================== -->
    <!-- TOP PMS STANDARD HEADER BANNER (Identical to Main Onb.)  -->
    <!-- ======================================================== -->
    <header class="bg-[#1A237E] text-white shadow-xl relative overflow-hidden">
      <!-- Decorative background accent circles -->
      <div class="absolute -right-10 -bottom-10 w-44 h-44 rounded-full bg-white/5 pointer-events-none"></div>
      <div class="absolute -left-10 -top-10 w-36 h-36 rounded-full bg-blue-500/10 pointer-events-none"></div>

      <div class="max-w-5xl mx-auto px-4 py-5 sm:px-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          
          <!-- Title & Subtitle with User Icon -->
          <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-blue-200 border border-white/15 shrink-0 shadow-inner">
              <i class="pi pi-user-plus text-2xl text-blue-200"></i>
            </div>
            <div>
              <p class="text-white/80 text-[10px] sm:text-xs font-black uppercase tracking-wider mb-0.5">
                HUMAN RESOURCES &amp; TALENT ACQUISITION
              </p>
              <h1 class="text-lg sm:text-2xl font-black text-white tracking-tight leading-tight m-0">
                Employee Onboarding &amp; Registration
              </h1>
              <p class="text-blue-100/70 text-xs font-medium mt-0.5 hidden sm:block">
                Complete registration, position details, banking, guarantee records and compliance checklists
              </p>
            </div>
          </div>

          <!-- Online Candidate Badge & Draft Status -->
          <div class="flex items-center gap-2 self-end sm:self-center">
            <div v-if="draftSavedAt" class="px-2.5 py-1 rounded-full bg-white/10 backdrop-blur border border-white/20 text-white text-[11px] font-semibold flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              <span>Draft saved {{ draftSavedAt }}</span>
            </div>
            <button
              type="button"
              @click="promptClearDraft"
              title="Reset and start clean form"
              class="px-2.5 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-bold transition flex items-center gap-1"
            >
              <i class="pi pi-refresh text-xs"></i>
              <span class="hidden sm:inline">Reset</span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-5xl mx-auto px-3 sm:px-6 pt-5 pb-12">
      
      <!-- ======================================================== -->
      <!-- SUCCESS SCREEN (Application Confirmation)                -->
      <!-- ======================================================== -->
      <div v-if="submittedSuccess" class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6 sm:p-10 text-center animate-fade-in">
        <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-5 shadow-inner">
          <i class="pi pi-check text-4xl font-black"></i>
        </div>
        <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-extrabold uppercase tracking-wider inline-block mb-3">
          Application Received
        </span>
        <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mb-2">
          Congratulations, {{ successData.candidate_name || emp.firstname }}!
        </h2>
        <p class="text-slate-600 max-w-lg mx-auto text-sm sm:text-base mb-6">
          Your Melcom employee onboarding registration has been securely transmitted to the Melcom HR Department.
        </p>

        <!-- Reference Box -->
        <div class="bg-slate-50 border-2 border-dashed border-slate-300 rounded-2xl p-5 max-w-md mx-auto mb-8 text-left">
          <div class="flex items-center justify-between mb-3 pb-3 border-b border-slate-200">
            <span class="text-xs uppercase font-bold text-slate-500">Tracking Reference</span>
            <button
              type="button"
              @click="copyReference(successData.reference_number)"
              class="text-xs text-[#1A237E] hover:underline font-bold flex items-center gap-1"
            >
              <i class="pi pi-copy text-xs"></i>
              <span>{{ copyStatusText }}</span>
            </button>
          </div>
          <div class="text-xl sm:text-2xl font-black text-slate-900 font-mono tracking-wider break-all mb-4 text-center py-2 bg-white rounded-lg border border-slate-200 shadow-xs">
            {{ successData.reference_number || 'MEL-ONB-PROCESSED' }}
          </div>

          <div class="space-y-2 text-xs">
            <div class="flex justify-between py-1 border-b border-slate-100">
              <span class="text-slate-500 font-medium">Position:</span>
              <span class="font-bold text-slate-800">{{ emp.joiningposition || 'Staff' }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
              <span class="text-slate-500 font-medium">Company / Branch:</span>
              <span class="font-bold text-slate-800">{{ getCompanyName(emp.joining_company_id) }} ({{ getBranchName(emp.joining_branch_id) }})</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
              <span class="text-slate-500 font-medium">Temporary Code:</span>
              <span class="font-mono font-bold text-[#1A237E]">{{ successData.employee_code || 'N/A' }}</span>
            </div>
            <div class="flex justify-between py-1">
              <span class="text-slate-500 font-medium">Submitted At:</span>
              <span class="font-semibold text-slate-700">{{ successData.submission_date || new Date().toLocaleString() }}</span>
            </div>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
          <button
            type="button"
            @click="printSummary"
            class="w-full sm:w-auto px-6 py-3 rounded-xl bg-[#1A237E] text-white font-bold text-sm hover:bg-[#121858] transition flex items-center justify-center gap-2 shadow-md"
          >
            <i class="pi pi-print"></i>
            Print / Save Confirmation (PDF)
          </button>
          <button
            type="button"
            @click="startNewApplication"
            class="w-full sm:w-auto px-6 py-3 rounded-xl border border-slate-300 text-slate-700 font-bold text-sm hover:bg-slate-100 transition"
          >
            Submit Another Application
          </button>
        </div>
      </div>

      <!-- ======================================================== -->
      <!-- MAIN ONBOARDING FORM UI (Exact Replica of Main Tabs)     -->
      <!-- ======================================================== -->
      <form v-else @submit.prevent="handleFormAction" novalidate>
        
        <!-- Modern PMS Filter Pill Tabs (Identical to Main Onboarding) -->
        <div class="tabs mb-5 bg-slate-200/80 p-1.5 rounded-2xl border border-slate-300 flex overflow-x-auto no-scrollbar gap-1.5 shadow-xs select-none">
          <button
            type="button"
            @click="maintab = 1"
            :class="maintab == 1 ? 'activetab' : ''"
            class="tab-btn shrink-0"
          >
            <i class="pi pi-user text-xs"></i>
            <span>Employee Information Form</span>
          </button>

          <button
            type="button"
            @click="maintab = 2"
            :class="maintab == 2 ? 'activetab' : ''"
            class="tab-btn shrink-0"
          >
            <i class="pi pi-credit-card text-xs"></i>
            <span>Bank / Social Security Fund</span>
          </button>

          <button
            type="button"
            @click="maintab = 3"
            :class="maintab == 3 ? 'activetab' : ''"
            class="tab-btn shrink-0"
          >
            <i class="pi pi-shield text-xs"></i>
            <span>Irrevocable Continuing Guarantee</span>
          </button>

          <button
            type="button"
            @click="maintab = 4"
            :class="maintab == 4 ? 'activetab' : ''"
            class="tab-btn shrink-0"
          >
            <i class="pi pi-check-square text-xs"></i>
            <span>Checklist &amp; Compliance</span>
          </button>
        </div>

        <!-- ======================================================== -->
        <!-- TAB 1: EMPLOYEE INFORMATION FORM                         -->
        <!-- ======================================================== -->
        <div v-show="maintab == 1" class="space-y-6">
          
          <!-- Sub-header with Title & Page Stepper [ 1 ] / [ 4 ] -->
          <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h2 class="text-lg sm:text-xl font-black text-slate-800 tracking-tight flex items-center gap-2 m-0">
                <span>Employee Information Form</span>
              </h2>
              <p class="text-xs text-slate-500 font-medium mt-0.5 mb-0">Capture employee personal, position, contact and educational details</p>
            </div>
            
            <!-- Page Stepper matching main onboarding -->
            <div class="flex items-center gap-2 self-start sm:self-center">
              <span class="text-xs font-black uppercase tracking-wider text-slate-500">PAGE</span>
              <div class="flex items-center gap-1">
                <button
                  v-for="p in 4"
                  :key="p"
                  type="button"
                  @click="perinfostep = p"
                  class="w-7 h-7 rounded-lg text-xs font-black transition flex items-center justify-center border"
                  :class="perinfostep === p ? 'bg-[#1A237E] text-white border-[#1A237E] shadow-sm' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                >
                  {{ p }}
                </button>
              </div>
              <span class="text-xs font-bold text-slate-400">/ 4</span>
            </div>
          </div>

          <!-- ─── PAGE 1: Position Details & Personal Information ─── -->
          <div v-show="perinfostep == 1" class="space-y-6 animate-fade-in">
            
            <!-- Section: MELCOM EMPLOYEE POSITION DETAILS -->
            <div class="section-card">
              <div class="section-header">
                <span class="accent-bar"></span>
                <span>MELCOM EMPLOYEE POSITION DETAILS</span>
              </div>
              
              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                  <div>
                    <label class="form-label">Joining Company *:</label>
                    <select v-model="emp.joining_company_id" class="form-select" required>
                      <option v-for="comp in companyList" :key="comp.id" :value="comp.id">{{ comp.name }}</option>
                    </select>
                  </div>

                  <div>
                    <label class="form-label">Contract Category *:</label>
                    <select v-model="emp.contracttype" class="form-select" required>
                      <option v-for="ct in contractTypes" :key="ct.code" :value="ct.code">{{ ct.name }}</option>
                    </select>
                  </div>

                  <div>
                    <label class="form-label">Joining Location *:</label>
                    <select v-model="emp.joining_branch_id" class="form-select" required>
                      <option value="" disabled>-- Select Location --</option>
                      <option v-for="b in branchList" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                  </div>

                  <div>
                    <label class="form-label">Joining Department *:</label>
                    <select v-model="emp.joining_dept_id" class="form-select" required>
                      <option value="" disabled>-- Select Department --</option>
                      <option v-for="d in deptList" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                  </div>

                  <div>
                    <label class="form-label">Joining Position *:</label>
                    <input
                      type="text"
                      v-model="emp.joiningposition"
                      placeholder="e.g. CASHIER, SALES EXECUTIVE"
                      @input="emp.joiningposition = (emp.joiningposition || '').toUpperCase()"
                      class="form-input uppercase"
                      required
                    />
                  </div>

                  <div>
                    <label class="form-label">Date of Joining *:</label>
                    <input
                      type="date"
                      v-model="emp.joiningdate"
                      class="form-input"
                      required
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Section: PERSONAL INFORMATION -->
            <div class="section-card">
              <div class="section-header">
                <span class="accent-bar"></span>
                <span>PERSONAL INFORMATION</span>
              </div>

              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs space-y-5">
                
                <!-- Profile Picture Upload Zone (Matching Main Onboarding) -->
                <div>
                  <label class="form-label mb-2">Profile Picture *:</label>
                  <div class="profile-upload-zone" @click="triggerProfileUpload">
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 text-center sm:text-left">
                      <div class="w-16 h-16 rounded-full bg-slate-100 border-2 border-slate-200 flex items-center justify-center overflow-hidden shrink-0 shadow-inner">
                        <img v-if="emp.profilepicture" :src="emp.profilepicture" alt="Profile Preview" class="w-full h-full object-cover" />
                        <i v-else class="pi pi-user text-2xl text-slate-400"></i>
                      </div>
                      <div>
                        <p class="font-bold text-slate-800 text-sm mb-0.5">Click to upload photo or take selfie</p>
                        <p class="text-xs text-slate-400 mb-2">PNG, JPG or JPEG up to 5MB</p>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#1A237E] text-white text-xs font-bold shadow-xs">
                          <i class="pi pi-camera"></i>
                          <span>{{ emp.profilepicture ? 'Change Photo' : 'Select Image' }}</span>
                        </span>
                        <input
                          ref="profileInputRef"
                          type="file"
                          accept="image/*"
                          capture="user"
                          @change="handleProfilePhoto"
                          class="hidden"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Personal Identity Fields Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                  <div>
                    <label class="form-label">First Name *:</label>
                    <input
                      type="text"
                      v-model="emp.firstname"
                      placeholder="e.g. KWAME"
                      @input="onNameChange"
                      class="form-input uppercase"
                      required
                    />
                  </div>

                  <div>
                    <label class="form-label">Surname *:</label>
                    <input
                      type="text"
                      v-model="emp.surname"
                      placeholder="e.g. MENSAH"
                      @input="onNameChange"
                      class="form-input uppercase"
                      required
                    />
                  </div>

                  <div>
                    <label class="form-label">Other Names:</label>
                    <input
                      type="text"
                      v-model="emp.othername"
                      placeholder="e.g. KOFI"
                      @input="emp.othername = (emp.othername || '').toUpperCase()"
                      class="form-input uppercase"
                    />
                  </div>

                  <div>
                    <label class="form-label">Gender *:</label>
                    <select v-model="emp.gender" class="form-select" required>
                      <option value="M">MALE</option>
                      <option value="F">FEMALE</option>
                    </select>
                  </div>

                  <div>
                    <div class="flex items-center justify-between mb-1">
                      <label class="form-label mb-0">Date of Birth *:</label>
                      <span v-if="computedAge !== null" class="text-[11px] font-black text-[#1A237E] px-2 py-0.5 bg-blue-50 border border-blue-200 rounded">
                        Age: {{ computedAge }} yrs
                      </span>
                    </div>
                    <input
                      type="date"
                      v-model="emp.dob"
                      :max="maxDobDate"
                      class="form-input"
                      required
                    />
                  </div>

                  <div>
                    <label class="form-label">Ghana Card No. *:</label>
                    <input
                      type="text"
                      v-model="emp.ghcardno"
                      placeholder="e.g. GHA-123456789-0"
                      @input="formatGhCard"
                      class="form-input uppercase font-mono"
                      required
                    />
                  </div>

                  <div>
                    <label class="form-label">Ghana Card Expiry Date:</label>
                    <input
                      type="date"
                      v-model="emp.ghcardexpiry"
                      class="form-input"
                    />
                  </div>

                  <div>
                    <label class="form-label">Mobile Phone Number *:</label>
                    <input
                      type="tel"
                      v-model="emp.mobileno"
                      placeholder="e.g. 0244123456"
                      class="form-input"
                      required
                    />
                  </div>

                  <div>
                    <label class="form-label">WhatsApp Number:</label>
                    <input
                      type="tel"
                      v-model="emp.whatsappno"
                      placeholder="e.g. 0550123456"
                      class="form-input"
                    />
                  </div>

                  <div>
                    <label class="form-label">Email Address:</label>
                    <input
                      type="email"
                      v-model="emp.email"
                      placeholder="e.g. kwame@example.com"
                      class="form-input"
                    />
                  </div>

                  <div>
                    <label class="form-label">Region of Residence *:</label>
                    <select v-model="emp.region_id" class="form-select" required>
                      <option v-for="r in regionList" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </select>
                  </div>

                  <div>
                    <label class="form-label">Hometown:</label>
                    <input
                      type="text"
                      v-model="emp.hometown"
                      placeholder="e.g. KUMASI"
                      @input="emp.hometown = (emp.hometown || '').toUpperCase()"
                      class="form-input uppercase"
                    />
                  </div>

                  <div>
                    <label class="form-label">GhanaPost GPS Digital Address:</label>
                    <input
                      type="text"
                      v-model="emp.gpsaddress"
                      placeholder="e.g. GA-183-9024"
                      @input="emp.gpsaddress = (emp.gpsaddress || '').toUpperCase()"
                      class="form-input uppercase font-mono"
                    />
                  </div>

                  <div>
                    <label class="form-label">SSNIT Number (Optional):</label>
                    <input
                      type="text"
                      v-model="emp.socialsecurityno"
                      placeholder="e.g. C012345678912"
                      @input="emp.socialsecurityno = (emp.socialsecurityno || '').toUpperCase()"
                      class="form-input uppercase font-mono"
                    />
                  </div>

                  <div class="sm:col-span-2">
                    <label class="form-label">Residential Address (Current Living) *:</label>
                    <input
                      type="text"
                      v-model="emp.resaddress"
                      placeholder="e.g. Hse No. 12, Spintex Road, near Shell, Accra"
                      @input="emp.resaddress = (emp.resaddress || '').toUpperCase()"
                      class="form-input uppercase"
                      required
                    />
                  </div>

                  <div>
                    <label class="form-label">Father's Full Name:</label>
                    <input
                      type="text"
                      v-model="emp.fathersname"
                      placeholder="e.g. JOHN MENSAH SR."
                      @input="emp.fathersname = (emp.fathersname || '').toUpperCase()"
                      class="form-input uppercase"
                    />
                  </div>

                  <div>
                    <label class="form-label">Mother's Full Name:</label>
                    <input
                      type="text"
                      v-model="emp.mothersname"
                      placeholder="e.g. MARY MENSAH"
                      @input="emp.mothersname = (emp.mothersname || '').toUpperCase()"
                      class="form-input uppercase"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ─── PAGE 2: Family, Spouse, Children, Nominee & Emergency ─── -->
          <div v-show="perinfostep == 2" class="space-y-6 animate-fade-in">
            
            <!-- Section: FAMILY & SPOUSE DETAILS -->
            <div class="section-card">
              <div class="section-header">
                <span class="accent-bar"></span>
                <span>FAMILY &amp; SPOUSE DETAILS</span>
              </div>
              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="form-label">Marital Status *:</label>
                    <select v-model="emp.maritalstatus" class="form-select" required>
                      <option v-for="m in maritalList" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                  </div>

                  <div v-if="emp.maritalstatus == 2">
                    <label class="form-label">Spouse Full Name:</label>
                    <input
                      type="text"
                      v-model="spouse.name"
                      placeholder="e.g. GRACE MENSAH"
                      @input="spouse.name = (spouse.name || '').toUpperCase()"
                      class="form-input uppercase"
                    />
                  </div>

                  <div v-if="emp.maritalstatus == 2">
                    <label class="form-label">Spouse Phone Number:</label>
                    <input
                      type="tel"
                      v-model="spouse.phoneno"
                      placeholder="e.g. 0244987654"
                      class="form-input"
                    />
                  </div>

                  <div v-if="emp.maritalstatus == 2">
                    <label class="form-label">Spouse Occupation:</label>
                    <input
                      type="text"
                      v-model="spouse.occupation"
                      placeholder="e.g. TEACHER, NURSE"
                      @input="spouse.occupation = (spouse.occupation || '').toUpperCase()"
                      class="form-input uppercase"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Section: CHILDREN DETAILS -->
            <div class="section-card">
              <div class="section-header justify-between">
                <div class="flex items-center gap-2">
                  <span class="accent-bar"></span>
                  <span>CHILDREN DETAILS</span>
                </div>
                <button
                  type="button"
                  @click="addChild"
                  class="px-2.5 py-1 rounded-lg bg-white/20 hover:bg-white/30 text-white text-[11px] font-bold transition flex items-center gap-1"
                >
                  <i class="pi pi-plus text-[10px]"></i>
                  <span>Add Child</span>
                </button>
              </div>

              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs">
                <div v-if="childrens.length === 0" class="p-5 text-center rounded-xl bg-slate-50 border border-dashed border-slate-200 text-xs text-slate-500 font-medium">
                  No dependent children added. If you have children, tap <strong>"Add Child"</strong> above.
                </div>
                <div v-else class="space-y-3">
                  <div
                    v-for="(child, idx) in childrens"
                    :key="idx"
                    class="p-4 rounded-xl bg-slate-50 border border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-3 items-end"
                  >
                    <div>
                      <label class="form-label">Child {{ idx + 1 }} Name:</label>
                      <input
                        type="text"
                        v-model="child.name"
                        placeholder="e.g. KOFI MENSAH"
                        @input="child.name = (child.name || '').toUpperCase()"
                        class="form-input uppercase"
                      />
                    </div>
                    <div>
                      <label class="form-label">Gender:</label>
                      <select v-model="child.gender" class="form-select">
                        <option value="M">MALE</option>
                        <option value="F">FEMALE</option>
                      </select>
                    </div>
                    <div class="flex items-center gap-2">
                      <div class="flex-1">
                        <label class="form-label">Date of Birth:</label>
                        <input type="date" v-model="child.dob" class="form-input" />
                      </div>
                      <button
                        type="button"
                        @click="removeChild(idx)"
                        class="h-11 w-11 rounded-xl text-slate-400 hover:text-red-600 hover:bg-red-50 border border-slate-200 transition flex items-center justify-center shrink-0"
                      >
                        <i class="pi pi-trash text-xs"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section: NOMINEE INFORMATION (NEXT OF KIN) -->
            <div class="section-card">
              <div class="section-header">
                <span class="accent-bar"></span>
                <span>NOMINEE INFORMATION (NEXT OF KIN)</span>
              </div>
              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="form-label">Nominee Full Name *:</label>
                    <input
                      type="text"
                      v-model="nominee.name"
                      placeholder="e.g. MARY MENSAH"
                      @input="nominee.name = (nominee.name || '').toUpperCase()"
                      class="form-input uppercase"
                      required
                    />
                  </div>

                  <div>
                    <label class="form-label">Relationship to You *:</label>
                    <select v-model="nominee.relationship" class="form-select" required>
                      <option value="MOTHER">MOTHER</option>
                      <option value="FATHER">FATHER</option>
                      <option value="SPOUSE">SPOUSE</option>
                      <option value="BROTHER">BROTHER</option>
                      <option value="SISTER">SISTER</option>
                      <option value="SON">SON</option>
                      <option value="DAUGHTER">DAUGHTER</option>
                      <option value="OTHER">OTHER</option>
                    </select>
                  </div>

                  <div>
                    <label class="form-label">Phone Number *:</label>
                    <input
                      type="tel"
                      v-model="nominee.phoneno"
                      placeholder="e.g. 0244112233"
                      class="form-input"
                      required
                    />
                  </div>

                  <div>
                    <label class="form-label">Residential Address:</label>
                    <input
                      type="text"
                      v-model="nominee.address"
                      placeholder="e.g. Hse No. 44, Dome Pillar 2, Accra"
                      @input="nominee.address = (nominee.address || '').toUpperCase()"
                      class="form-input uppercase"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Section: EMERGENCY CONTACT INFORMATION [RELATIVES ONLY] -->
            <div class="section-card">
              <div class="section-header">
                <span class="accent-bar"></span>
                <span>EMERGENCY CONTACT INFORMATION [RELATIVES ONLY]</span>
              </div>
              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs space-y-4">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                  <span class="text-xs font-black uppercase text-[#1A237E] mb-3 block">Primary Relative Contact (Required)</span>
                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                      <label class="form-label">Relative Name *:</label>
                      <input
                        type="text"
                        v-model="econts[0].name"
                        placeholder="e.g. KWAME MENSAH"
                        @input="econts[0].name = (econts[0].name || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                    <div>
                      <label class="form-label">Relationship *:</label>
                      <input
                        type="text"
                        v-model="econts[0].relationship"
                        placeholder="e.g. BROTHER, MOTHER"
                        @input="econts[0].relationship = (econts[0].relationship || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                    <div>
                      <label class="form-label">Mobile Phone *:</label>
                      <input
                        type="tel"
                        v-model="econts[0].phoneno"
                        placeholder="e.g. 0244001122"
                        class="form-input"
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ─── PAGE 3: Education, Work Experience & References ─── -->
          <div v-show="perinfostep == 3" class="space-y-6 animate-fade-in">
            
            <!-- Section: EDUCATIONAL BACKGROUND -->
            <div class="section-card">
              <div class="section-header justify-between">
                <div class="flex items-center gap-2">
                  <span class="accent-bar"></span>
                  <span>EDUCATIONAL BACKGROUND</span>
                </div>
                <button
                  type="button"
                  @click="addEducation"
                  class="px-2.5 py-1 rounded-lg bg-white/20 hover:bg-white/30 text-white text-[11px] font-bold transition flex items-center gap-1"
                >
                  <i class="pi pi-plus text-[10px]"></i>
                  <span>Add School</span>
                </button>
              </div>

              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs space-y-4">
                <div
                  v-for="(edu, idx) in edus"
                  :key="idx"
                  class="p-4 rounded-xl bg-slate-50 border border-slate-200"
                >
                  <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-black uppercase text-[#1A237E]">Education Entry #{{ idx + 1 }}</span>
                    <button
                      v-if="edus.length > 1"
                      type="button"
                      @click="removeEducation(idx)"
                      class="text-xs text-red-600 font-bold hover:underline"
                    >
                      Remove
                    </button>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                      <label class="form-label">School / Institution Name *:</label>
                      <input
                        type="text"
                        v-model="edu.schoolname"
                        placeholder="e.g. ACCRA ACADEMY / ACCRA TECHNICAL UNIVERSITY"
                        @input="edu.schoolname = (edu.schoolname || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                    <div>
                      <label class="form-label">Qualification *:</label>
                      <select v-model="edu.qualification" class="form-select" required>
                        <option value="WASSCE / SSCE">WASSCE / SSCE</option>
                        <option value="BECE">BECE</option>
                        <option value="HND">HND (DIPLOMA)</option>
                        <option value="BACHELOR DEGREE">BACHELOR'S DEGREE</option>
                        <option value="MASTERS DEGREE">MASTER'S DEGREE</option>
                        <option value="OTHER">OTHER</option>
                      </select>
                    </div>
                    <div>
                      <label class="form-label">Course / Programme:</label>
                      <input
                        type="text"
                        v-model="edu.coursetitle"
                        placeholder="e.g. GENERAL ARTS, ACCOUNTING"
                        @input="edu.coursetitle = (edu.coursetitle || '').toUpperCase()"
                        class="form-input uppercase"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section: WORK EXPERIENCE -->
            <div class="section-card">
              <div class="section-header justify-between">
                <div class="flex items-center gap-2">
                  <span class="accent-bar"></span>
                  <span>WORK EXPERIENCE</span>
                </div>
                <button
                  type="button"
                  @click="addWorkExp"
                  class="px-2.5 py-1 rounded-lg bg-white/20 hover:bg-white/30 text-white text-[11px] font-bold transition flex items-center gap-1"
                >
                  <i class="pi pi-plus text-[10px]"></i>
                  <span>Add Experience</span>
                </button>
              </div>

              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs">
                <div v-if="workexps.length === 0" class="p-5 text-center rounded-xl bg-slate-50 border border-dashed border-slate-200 text-xs text-slate-500 font-medium">
                  No prior work experience listed. If you have past employment, tap <strong>"Add Experience"</strong> above.
                </div>
                <div v-else class="space-y-4">
                  <div
                    v-for="(w, idx) in workexps"
                    :key="idx"
                    class="p-4 rounded-xl bg-slate-50 border border-slate-200"
                  >
                    <div class="flex items-center justify-between mb-3">
                      <span class="text-xs font-black uppercase text-[#1A237E]">Experience #{{ idx + 1 }}</span>
                      <button type="button" @click="removeWorkExp(idx)" class="text-xs text-red-600 font-bold hover:underline">
                        Remove
                      </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <div>
                        <label class="form-label">Employer / Company Name:</label>
                        <input
                          type="text"
                          v-model="w.companyname"
                          placeholder="e.g. ABC LOGISTICS"
                          @input="w.companyname = (w.companyname || '').toUpperCase()"
                          class="form-input uppercase"
                        />
                      </div>
                      <div>
                        <label class="form-label">Position / Job Title:</label>
                        <input
                          type="text"
                          v-model="w.jobtitle"
                          placeholder="e.g. SALES ASSISTANT"
                          @input="w.jobtitle = (w.jobtitle || '').toUpperCase()"
                          class="form-input uppercase"
                        />
                      </div>
                      <div>
                        <label class="form-label">Reason for Leaving:</label>
                        <input
                          type="text"
                          v-model="w.reasonforleaving"
                          placeholder="e.g. Career growth"
                          class="form-input"
                        />
                      </div>
                      <div>
                        <label class="form-label">Salary (Optional):</label>
                        <input
                          type="text"
                          v-model="w.salary"
                          placeholder="e.g. GHS 1,500"
                          class="form-input"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section: REFERENCES -->
            <div class="section-card">
              <div class="section-header justify-between">
                <div class="flex items-center gap-2">
                  <span class="accent-bar"></span>
                  <span>REFERENCES</span>
                </div>
                <button
                  type="button"
                  @click="addReference"
                  class="px-2.5 py-1 rounded-lg bg-white/20 hover:bg-white/30 text-white text-[11px] font-bold transition flex items-center gap-1"
                >
                  <i class="pi pi-plus text-[10px]"></i>
                  <span>Add Reference</span>
                </button>
              </div>

              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs space-y-4">
                <div
                  v-for="(ref, idx) in refs"
                  :key="idx"
                  class="p-4 rounded-xl bg-slate-50 border border-slate-200"
                >
                  <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-black uppercase text-[#1A237E]">Referee #{{ idx + 1 }}</span>
                    <button v-if="refs.length > 1" type="button" @click="removeReference(idx)" class="text-xs text-red-600 font-bold hover:underline">
                      Remove
                    </button>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label class="form-label">Referee Full Name *:</label>
                      <input
                        type="text"
                        v-model="ref.name"
                        placeholder="e.g. REV. DANIEL APPIAH"
                        @input="ref.name = (ref.name || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                    <div>
                      <label class="form-label">Organization / Church:</label>
                      <input
                        type="text"
                        v-model="ref.organization"
                        placeholder="e.g. METHODIST CHURCH"
                        @input="ref.organization = (ref.organization || '').toUpperCase()"
                        class="form-input uppercase"
                      />
                    </div>
                    <div>
                      <label class="form-label">Position / Role:</label>
                      <input
                        type="text"
                        v-model="ref.position"
                        placeholder="e.g. HEADMASTER, SENIOR PASTOR"
                        @input="ref.position = (ref.position || '').toUpperCase()"
                        class="form-input uppercase"
                      />
                    </div>
                    <div>
                      <label class="form-label">Phone Number *:</label>
                      <input
                        type="tel"
                        v-model="ref.phoneno"
                        placeholder="e.g. 0244778899"
                        class="form-input"
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ─── PAGE 4: Guarantor Details ─── -->
          <div v-show="perinfostep == 4" class="space-y-6 animate-fade-in">
            
            <!-- Section: GUARANTOR DETAILS -->
            <div class="section-card">
              <div class="section-header">
                <span class="accent-bar"></span>
                <span>GUARANTOR DETAILS</span>
              </div>

              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs">
                <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-200 text-[#1A237E] text-xs font-medium mb-5 flex items-start gap-2">
                  <i class="pi pi-info-circle text-blue-700 text-sm mt-0.5"></i>
                  <span>Melcom Group requires a credible guarantor (e.g. senior professional, teacher, pastor, business owner).</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="form-label">Guarantor Full Name *:</label>
                    <input
                      type="text"
                      v-model="guarantor.name"
                      placeholder="e.g. DR. EMMANUEL ADDO"
                      @input="guarantor.name = (guarantor.name || '').toUpperCase()"
                      class="form-input uppercase"
                      required
                    />
                  </div>

                  <div>
                    <label class="form-label">Relationship to Candidate *:</label>
                    <input
                      type="text"
                      v-model="guarantor.relation"
                      placeholder="e.g. UNCLE, SENIOR PASTOR"
                      @input="guarantor.relation = (guarantor.relation || '').toUpperCase()"
                      class="form-input uppercase"
                      required
                    />
                  </div>

                  <div>
                    <label class="form-label">Guarantor Mobile Phone *:</label>
                    <input
                      type="tel"
                      v-model="guarantor.phoneno"
                      placeholder="e.g. 0244123890"
                      class="form-input"
                      required
                    />
                  </div>

                  <div>
                    <label class="form-label">Guarantor Occupation:</label>
                    <input
                      type="text"
                      v-model="guarantor.occupation"
                      placeholder="e.g. MEDICAL DOCTOR, ACCOUNTANT"
                      @input="guarantor.occupation = (guarantor.occupation || '').toUpperCase()"
                      class="form-input uppercase"
                    />
                  </div>

                  <div>
                    <label class="form-label">Employer / Workplace:</label>
                    <input
                      type="text"
                      v-model="guarantor.employer"
                      placeholder="e.g. KORLE BU HOSPITAL"
                      @input="guarantor.employer = (guarantor.employer || '').toUpperCase()"
                      class="form-input uppercase"
                    />
                  </div>

                  <div>
                    <label class="form-label">Guarantor Ghana Card No.:</label>
                    <input
                      type="text"
                      v-model="guarantor.ghcardno"
                      placeholder="e.g. GHA-987654321-0"
                      @input="guarantor.ghcardno = (guarantor.ghcardno || '').toUpperCase()"
                      class="form-input uppercase font-mono"
                    />
                  </div>

                  <div class="sm:col-span-2">
                    <label class="form-label">Guarantor Residential Address:</label>
                    <input
                      type="text"
                      v-model="guarantor.address"
                      placeholder="e.g. Plot 15, East Legon Hills, Accra"
                      @input="guarantor.address = (guarantor.address || '').toUpperCase()"
                      class="form-input uppercase"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- TAB 2: BANK / SOCIAL SECURITY FUND                       -->
        <!-- ======================================================== -->
        <div v-show="maintab == 2" class="space-y-6 animate-fade-in">
          <div class="section-card">
            <div class="section-header">
              <span class="accent-bar"></span>
              <span>BANK / SOCIAL SECURITY FUND</span>
            </div>

            <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs">
              <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-200 text-[#1A237E] text-xs font-medium mb-5 flex items-start gap-2">
                <i class="pi pi-info-circle text-blue-700 text-sm mt-0.5"></i>
                <span>Your monthly salary and allowances will be processed directly into this bank account in your name.</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                <div>
                  <label class="form-label">Bank Name *:</label>
                  <select v-model="banksocial.bankname" class="form-select" required>
                    <option value="" disabled>-- Select Your Bank --</option>
                    <option v-for="b in bankList" :key="b.id" :value="b.id">{{ b.name }}</option>
                  </select>
                </div>

                <div>
                  <label class="form-label">Bank Branch *:</label>
                  <input
                    type="text"
                    v-model="banksocial.bankbranch"
                    placeholder="e.g. SPINTEX ROAD BRANCH"
                    @input="banksocial.bankbranch = (banksocial.bankbranch || '').toUpperCase()"
                    class="form-input uppercase"
                    required
                  />
                </div>

                <div>
                  <label class="form-label">Account Name *:</label>
                  <input
                    type="text"
                    v-model="banksocial.accountname"
                    placeholder="e.g. KWAME MENSAH"
                    @input="banksocial.accountname = (banksocial.accountname || '').toUpperCase()"
                    class="form-input uppercase"
                    required
                  />
                </div>

                <div>
                  <label class="form-label">Account Number *:</label>
                  <input
                    type="text"
                    v-model="banksocial.accountnumber"
                    placeholder="e.g. 1011234567890"
                    class="form-input font-mono font-bold"
                    required
                  />
                </div>

                <div>
                  <label class="form-label">Account Type *:</label>
                  <select v-model="banksocial.accounttype" class="form-select" required>
                    <option :value="1">CURRENT ACCOUNT</option>
                    <option :value="2">SAVINGS ACCOUNT</option>
                  </select>
                </div>

                <div>
                  <label class="form-label">Tier 2 Pension Number (Petra Trust):</label>
                  <input
                    type="text"
                    v-model="banksocial.petratrustnumber"
                    placeholder="e.g. PT-123456"
                    @input="banksocial.petratrustnumber = (banksocial.petratrustnumber || '').toUpperCase()"
                    class="form-input uppercase font-mono"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- TAB 3: IRREVOCABLE CONTINUING GUARANTEE                  -->
        <!-- ======================================================== -->
        <div v-show="maintab == 3" class="space-y-6 animate-fade-in">
          <div class="section-card">
            <div class="section-header">
              <span class="accent-bar"></span>
              <span>IRREVOCABLE CONTINUING GUARANTEE</span>
            </div>

            <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs space-y-4">
              <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 leading-relaxed space-y-2">
                <p class="font-medium">
                  In consideration of Melcom Group offering employment to <strong>{{ emp.firstname || 'the Candidate' }} {{ emp.surname }}</strong>, the guarantor acknowledges and agrees to stand as surety for the faithful and diligent execution of all duties and obligations.
                </p>
                <div class="flex items-center gap-3 pt-2">
                  <label class="font-black text-slate-800">Guarantee Indemnity Amount:</label>
                  <span class="font-extrabold font-mono text-[#1A237E] bg-blue-50 px-2.5 py-1 rounded-md border border-blue-200">GHS 5,000.00</span>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                  <label class="form-label">Guarantor Name:</label>
                  <input type="text" :value="guarantor.name" readonly class="form-input bg-slate-50 uppercase text-slate-600" />
                </div>
                <div>
                  <label class="form-label">Guarantor Phone:</label>
                  <input type="text" :value="guarantor.phoneno" readonly class="form-input bg-slate-50 text-slate-600" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- TAB 4: CHECKLIST & COMPLIANCE (Uploads & Declaration)    -->
        <!-- ======================================================== -->
        <div v-show="maintab == 4" class="space-y-6 animate-fade-in">
          
          <!-- Section: REQUIRED DOCUMENT UPLOADS -->
          <div class="section-card">
            <div class="section-header">
              <span class="accent-bar"></span>
              <span>REQUIRED DOCUMENT UPLOADS</span>
            </div>

            <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Upload Ghana Card Front -->
                <div class="p-4 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 text-center relative hover:border-[#1A237E] transition">
                  <span class="text-xs font-black uppercase text-slate-800 block mb-2">Ghana Card (Front Side) *</span>
                  <div v-if="documents.ghcard_front" class="relative mb-2">
                    <img :src="documents.ghcard_front" alt="Ghana Card Front" class="max-h-36 mx-auto rounded-lg shadow-sm border border-slate-200 object-cover" />
                    <button type="button" @click="documents.ghcard_front = null" class="absolute top-1 right-1 bg-red-600 text-white rounded-full p-1 text-[10px]">
                      <i class="pi pi-times"></i>
                    </button>
                  </div>
                  <div v-else class="py-3">
                    <i class="pi pi-id-card text-3xl text-slate-400 mb-2 block"></i>
                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-[#1A237E] text-white text-xs font-bold shadow-xs">
                      <i class="pi pi-camera"></i>
                      <span>Take / Upload Front</span>
                      <input type="file" accept="image/*" @change="e => handleDocUpload(e, 'ghcard_front')" class="hidden" />
                    </label>
                  </div>
                </div>

                <!-- Upload Ghana Card Back -->
                <div class="p-4 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 text-center relative hover:border-[#1A237E] transition">
                  <span class="text-xs font-black uppercase text-slate-800 block mb-2">Ghana Card (Back Side)</span>
                  <div v-if="documents.ghcard_back" class="relative mb-2">
                    <img :src="documents.ghcard_back" alt="Ghana Card Back" class="max-h-36 mx-auto rounded-lg shadow-sm border border-slate-200 object-cover" />
                    <button type="button" @click="documents.ghcard_back = null" class="absolute top-1 right-1 bg-red-600 text-white rounded-full p-1 text-[10px]">
                      <i class="pi pi-times"></i>
                    </button>
                  </div>
                  <div v-else class="py-3">
                    <i class="pi pi-id-card text-3xl text-slate-400 mb-2 block"></i>
                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800 text-white text-xs font-bold shadow-xs">
                      <i class="pi pi-camera"></i>
                      <span>Take / Upload Back</span>
                      <input type="file" accept="image/*" @change="e => handleDocUpload(e, 'ghcard_back')" class="hidden" />
                    </label>
                  </div>
                </div>

                <!-- Upload CV / Resume -->
                <div class="p-4 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 text-center relative hover:border-[#1A237E] transition">
                  <span class="text-xs font-black uppercase text-slate-800 block mb-2">Curriculum Vitae (CV / Resume)</span>
                  <div v-if="documents.resume_cv" class="py-3 flex items-center justify-center gap-2">
                    <i class="pi pi-file-pdf text-2xl text-red-600"></i>
                    <span class="text-xs font-bold text-slate-800">CV Attached</span>
                    <button type="button" @click="documents.resume_cv = null" class="text-xs text-red-600 font-bold hover:underline">Remove</button>
                  </div>
                  <div v-else class="py-3">
                    <i class="pi pi-file text-3xl text-slate-400 mb-2 block"></i>
                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800 text-white text-xs font-bold shadow-xs">
                      <i class="pi pi-upload"></i>
                      <span>Upload CV Document</span>
                      <input type="file" accept="application/pdf,image/*" @change="e => handleDocUpload(e, 'resume_cv')" class="hidden" />
                    </label>
                  </div>
                </div>

                <!-- Upload Certificate -->
                <div class="p-4 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 text-center relative hover:border-[#1A237E] transition">
                  <span class="text-xs font-black uppercase text-slate-800 block mb-2">Educational Certificate</span>
                  <div v-if="documents.certificate" class="py-3 flex items-center justify-center gap-2">
                    <i class="pi pi-file-check text-2xl text-emerald-600"></i>
                    <span class="text-xs font-bold text-slate-800">Certificate Attached</span>
                    <button type="button" @click="documents.certificate = null" class="text-xs text-red-600 font-bold hover:underline">Remove</button>
                  </div>
                  <div v-else class="py-3">
                    <i class="pi pi-book text-3xl text-slate-400 mb-2 block"></i>
                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800 text-white text-xs font-bold shadow-xs">
                      <i class="pi pi-upload"></i>
                      <span>Upload Certificate</span>
                      <input type="file" accept="application/pdf,image/*" @change="e => handleDocUpload(e, 'certificate')" class="hidden" />
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Section: TOUCH DIGITAL SIGNATURE -->
          <div class="section-card">
            <div class="section-header justify-between">
              <div class="flex items-center gap-2">
                <span class="accent-bar"></span>
                <span>TOUCH DIGITAL SIGNATURE</span>
              </div>
              <button
                type="button"
                @click="clearSignature"
                class="px-2.5 py-1 rounded-lg bg-white/20 hover:bg-white/30 text-white text-[11px] font-bold transition flex items-center gap-1"
              >
                <i class="pi pi-refresh text-[10px]"></i>
                <span>Clear</span>
              </button>
            </div>

            <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs">
              <p class="text-xs text-slate-500 font-medium mb-3">Sign with your finger (mobile) or mouse cursor inside the box below.</p>
              
              <div class="relative bg-white rounded-xl border-2 border-slate-300 overflow-hidden touch-none shadow-inner">
                <canvas
                  ref="sigCanvas"
                  class="w-full h-44 sm:h-52 bg-white cursor-crosshair block"
                  @mousedown="startDrawing"
                  @mousemove="draw"
                  @mouseup="stopDrawing"
                  @mouseleave="stopDrawing"
                  @touchstart.prevent="handleTouchStart"
                  @touchmove.prevent="handleTouchMove"
                  @touchend.prevent="handleTouchEnd"
                ></canvas>

                <div class="absolute bottom-4 left-6 right-6 border-b border-dashed border-slate-300 pointer-events-none flex justify-between text-[10px] text-slate-400 font-bold pb-1">
                  <span>Sign above this line</span>
                  <span>Finger / Stylus Pad</span>
                </div>

                <div v-if="hasSignature" class="absolute top-3 right-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-black px-2.5 py-1 rounded-full flex items-center gap-1 shadow-xs pointer-events-none">
                  <i class="pi pi-check text-[10px]"></i>
                  <span>Signature Captured</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Section: EMPLOYEE'S DECLARATION -->
          <div class="section-card">
            <div class="section-header">
              <span class="accent-bar"></span>
              <span>EMPLOYEE'S DECLARATION</span>
            </div>

            <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs">
              <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 leading-relaxed mb-4">
                <p class="font-medium">
                  I hereby solemnly declare that all details, certificates, and information provided in this onboarding application are true and correct. I understand that any false declaration or omission of material facts may result in rejection of offer or immediate termination of employment at Melcom Group.
                </p>
              </div>

              <label class="flex items-start gap-3 cursor-pointer select-none p-3.5 rounded-xl border border-slate-200 hover:bg-slate-50 transition">
                <input
                  type="checkbox"
                  v-model="agreedDeclaration"
                  class="mt-0.5 h-5 w-5 rounded text-[#1A237E] focus:ring-[#1A237E] border-slate-300 cursor-pointer"
                  required
                />
                <span class="text-xs font-bold text-slate-800 leading-snug">
                  I agree to the Melcom Employee Declaration and confirm the accuracy of all submitted details. *
                </span>
              </label>
            </div>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- DESKTOP ACTION BAR                                       -->
        <!-- ======================================================== -->
        <div class="hidden sm:flex items-center justify-between pt-6 mt-6 border-t border-slate-200">
          <button
            v-if="canGoBack"
            type="button"
            @click="handleBack"
            class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-100 transition flex items-center gap-2"
          >
            <i class="pi pi-arrow-left text-xs"></i>
            <span>Previous</span>
          </button>
          <div v-else></div>

          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="saveDraftToStorage(true)"
              class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:text-slate-900 font-semibold text-xs transition"
            >
              Save Draft
            </button>

            <button
              v-if="!isLastStep"
              type="button"
              @click="handleNext"
              class="px-7 py-2.5 rounded-xl bg-[#1A237E] hover:bg-[#121858] text-white font-black text-xs transition flex items-center gap-2 shadow-md shadow-indigo-100"
            >
              <span>Continue</span>
              <i class="pi pi-arrow-right text-xs"></i>
            </button>

            <button
              v-else
              type="submit"
              :disabled="submitting"
              class="px-8 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs transition flex items-center gap-2 shadow-lg shadow-emerald-200 disabled:opacity-50"
            >
              <i v-if="submitting" class="pi pi-spin pi-spinner text-xs"></i>
              <i v-else class="pi pi-check text-xs"></i>
              <span>{{ submitting ? 'Submitting Application...' : 'Submit Application' }}</span>
            </button>
          </div>
        </div>
      </form>
    </main>

    <!-- ======================================================== -->
    <!-- STICKY MOBILE BOTTOM BAR (Smartphone-First UX)           -->
    <!-- ======================================================== -->
    <div v-if="!submittedSuccess" class="sm:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur border-t border-slate-200 p-2.5 shadow-2xl">
      <div class="flex items-center justify-between gap-2 max-w-lg mx-auto">
        <button
          v-if="canGoBack"
          type="button"
          @click="handleBack"
          class="h-11 px-3.5 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs flex items-center justify-center gap-1 active:bg-slate-100"
        >
          <i class="pi pi-arrow-left text-xs"></i>
          <span>Back</span>
        </button>
        <button
          v-else
          type="button"
          @click="saveDraftToStorage(true)"
          class="h-11 px-3 rounded-xl border border-slate-200 text-slate-600 font-semibold text-xs active:bg-slate-100"
        >
          Save
        </button>

        <div class="text-center px-1">
          <span class="text-[11px] font-black text-[#1A237E] block truncate max-w-[140px]">
            {{ maintab === 1 ? `Page ${perinfostep} of 4` : currentTabName }}
          </span>
        </div>

        <button
          v-if="!isLastStep"
          type="button"
          @click="handleNext"
          class="h-11 px-5 rounded-xl bg-[#1A237E] text-white font-black text-xs flex items-center justify-center gap-1.5 shadow-md shadow-indigo-200 active:bg-[#121858]"
        >
          <span>Continue</span>
          <i class="pi pi-arrow-right text-xs"></i>
        </button>

        <button
          v-else
          type="button"
          @click="handleFormAction"
          :disabled="submitting"
          class="h-11 px-5 rounded-xl bg-emerald-600 text-white font-black text-xs flex items-center justify-center gap-1.5 shadow-lg shadow-emerald-200 disabled:opacity-50 active:bg-emerald-700"
        >
          <i v-if="submitting" class="pi pi-spin pi-spinner text-xs"></i>
          <span>{{ submitting ? 'Submitting...' : 'Submit Form' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import {
  companies as masterCompanies,
  depts as masterDepts,
  branchs as masterBranches,
  regions as masterRegions,
  banks as masterBanks,
  conttypes as masterConttypes,
  mstatus as masterMstatus
} from '@/data/masterdata'

// Master data lists
const companyList = ref(masterCompanies || [])
const deptList = ref(masterDepts || [])
const branchList = ref(masterBranches || [])
const regionList = ref(masterRegions || [])
const bankList = ref(masterBanks || [])
const contractTypes = ref(masterConttypes || [
  { code: 'contract', name: 'Contract' },
  { code: 'permanent', name: 'Permanent' },
  { code: 'probation', name: 'Probationary' },
  { code: 'casual', name: 'Casual' },
  { code: 'expat', name: 'Expat' }
])
const maritalList = ref(masterMstatus || [
  { id: 5, name: 'SINGLE' },
  { id: 2, name: 'MARRIED' },
  { id: 1, name: 'DIVORCED' },
  { id: 4, name: 'OTHERS' },
  { id: 3, name: 'NOT DISCLOSED' }
])

// Main Tabs matching Main Onboarding
const maintab = ref(1) // 1 = Form, 2 = Bank, 3 = Guarantee, 4 = Checklist
const perinfostep = ref(1) // 1, 2, 3, 4

const profileInputRef = ref(null)

const triggerProfileUpload = () => {
  if (profileInputRef.value) {
    profileInputRef.value.click()
  }
}

// Form State
const emp = reactive({
  joining_company_id: 3, // Melcom default
  contracttype: 'contract',
  joining_branch_id: 1,
  joining_dept_id: 34, // Retail
  joiningposition: '',
  joiningdate: new Date().toISOString().slice(0, 10),
  firstname: '',
  surname: '',
  othername: '',
  gender: 'M',
  dob: '',
  ghcardno: '',
  ghcardexpiry: '',
  mobileno: '',
  whatsappno: '',
  email: '',
  socialsecurityno: '',
  region_id: 7, // Greater Accra default
  hometown: '',
  gpsaddress: '',
  resaddress: '',
  fathersname: '',
  mothersname: '',
  maritalstatus: 5, // Single
  profilepicture: null,
  signature: null
})

const spouse = reactive({
  name: '',
  phoneno: '',
  occupation: '',
  address: ''
})

const childrens = ref([])

const nominee = reactive({
  name: '',
  relationship: 'MOTHER',
  phoneno: '',
  address: ''
})

const econts = ref([
  { name: '', relationship: 'MOTHER', phoneno: '', altphoneno: '', address: '' }
])

const edus = ref([
  { schoolname: '', qualification: 'WASSCE / SSCE', coursetitle: '', fromdate: '', todate: '' }
])

const workexps = ref([])

const refs = ref([
  { name: '', position: '', organization: '', phoneno: '', email: '' }
])

const banksocial = reactive({
  bankname: '',
  bankbranch: '',
  accountname: '',
  accountnumber: '',
  accounttype: 1, // Current
  petratrustnumber: ''
})

const guarantor = reactive({
  name: '',
  relation: '',
  phoneno: '',
  occupation: '',
  employer: '',
  ghcardno: '',
  address: ''
})

const documents = reactive({
  ghcard_front: null,
  ghcard_back: null,
  resume_cv: null,
  certificate: null
})

const agreedDeclaration = ref(false)
const submitting = ref(false)
const submittedSuccess = ref(false)
const successData = reactive({
  reference_number: '',
  employee_code: '',
  candidate_name: '',
  position: '',
  submission_date: ''
})
const draftSavedAt = ref('')
const copyStatusText = ref('Copy')

// Canvas Signature
const sigCanvas = ref(null)
const isDrawing = ref(false)
const hasSignature = ref(false)
let canvasCtx = null

// Live Age Calculation
const computedAge = computed(() => {
  if (!emp.dob) return null
  const birth = new Date(emp.dob)
  const now = new Date()
  if (isNaN(birth.getTime())) return null
  let age = now.getFullYear() - birth.getFullYear()
  const m = now.getMonth() - birth.getMonth()
  if (m < 0 || (m === 0 && now.getDate() < birth.getDate())) {
    age--
  }
  return age >= 0 ? age : null
})

// DOB Constraints (must be at least 15 years old)
const maxDobDate = computed(() => {
  const d = new Date()
  d.setFullYear(d.getFullYear() - 15)
  return d.toISOString().slice(0, 10)
})

// Navigation helpers
const canGoBack = computed(() => {
  return maintab.value > 1 || perinfostep.value > 1
})

const isLastStep = computed(() => {
  return maintab.value === 4
})

const currentTabName = computed(() => {
  if (maintab.value === 1) return 'Employee Form'
  if (maintab.value === 2) return 'Bank Details'
  if (maintab.value === 3) return 'Guarantee'
  return 'Compliance & Submit'
})

// Back & Next handlers
const handleBack = () => {
  if (maintab.value === 1) {
    if (perinfostep.value > 1) {
      perinfostep.value--
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }
  } else {
    maintab.value--
    if (maintab.value === 1) perinfostep.value = 4
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
  saveDraftToStorage()
}

const handleNext = () => {
  if (maintab.value === 1) {
    if (validatePage(perinfostep.value)) {
      if (perinfostep.value < 4) {
        perinfostep.value++
      } else {
        maintab.value = 2
      }
      window.scrollTo({ top: 0, behavior: 'smooth' })
      saveDraftToStorage()
    }
  } else if (maintab.value === 2) {
    if (validateBank()) {
      maintab.value = 3
      window.scrollTo({ top: 0, behavior: 'smooth' })
      saveDraftToStorage()
    }
  } else if (maintab.value === 3) {
    maintab.value = 4
    window.scrollTo({ top: 0, behavior: 'smooth' })
    saveDraftToStorage()
    nextTick(initCanvas)
  }
}

const handleFormAction = () => {
  if (!isLastStep.value) {
    handleNext()
  } else {
    submitApplication()
  }
}

// Validations
const validatePage = (step) => {
  const errors = []
  if (step === 1) {
    if (!emp.joining_company_id) errors.push('Please select a Joining Company.')
    if (!emp.joining_branch_id) errors.push('Please select your assigned Location.')
    if (!emp.joining_dept_id) errors.push('Please select your Department.')
    if (!emp.joiningposition || !emp.joiningposition.trim()) errors.push('Please enter your Job Title / Position.')
    if (!emp.firstname || !emp.firstname.trim()) errors.push('Please enter your First Name.')
    if (!emp.surname || !emp.surname.trim()) errors.push('Please enter your Surname.')
    if (!emp.dob) errors.push('Please select your Date of Birth.')
    if (!emp.ghcardno || emp.ghcardno.length < 10) errors.push('Please enter a valid Ghana Card Number.')
    if (!emp.mobileno || emp.mobileno.length < 9) errors.push('Please enter a valid Mobile Phone Number.')
    if (!emp.resaddress || !emp.resaddress.trim()) errors.push('Please enter your Residential Address.')
  } else if (step === 2) {
    if (!nominee.name || !nominee.name.trim()) errors.push('Please enter Nominee (Next of Kin) Full Name.')
    if (!nominee.phoneno || nominee.phoneno.length < 9) errors.push('Please enter Nominee Phone Number.')
    if (!econts.value[0].name || !econts.value[0].name.trim()) errors.push('Please enter Emergency Contact Name.')
    if (!econts.value[0].phoneno || econts.value[0].phoneno.length < 9) errors.push('Please enter Emergency Contact Phone.')
  } else if (step === 3) {
    if (edus.value.length === 0 || !edus.value[0].schoolname || !edus.value[0].schoolname.trim()) {
      errors.push('Please provide at least one school under Educational Background.')
    }
    if (refs.value.length === 0 || !refs.value[0].name || !refs.value[0].name.trim()) {
      errors.push('Please provide at least one character / professional reference.')
    }
  } else if (step === 4) {
    if (!guarantor.name || !guarantor.name.trim()) errors.push('Please provide your Guarantor Full Name.')
    if (!guarantor.relation || !guarantor.relation.trim()) errors.push('Please enter Relationship to Guarantor.')
    if (!guarantor.phoneno || guarantor.phoneno.length < 9) errors.push('Please enter Guarantor Phone Number.')
  }

  if (errors.length > 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Missing Required Fields',
      html: `<div class="text-left text-sm text-slate-700"><ul>${errors.map(e => `<li class="py-1">• ${e}</li>`).join('')}</ul></div>`,
      confirmButtonColor: '#1A237E'
    })
    return false
  }
  return true
}

const validateBank = () => {
  const errors = []
  if (!banksocial.bankname) errors.push('Please select your Bank Name.')
  if (!banksocial.bankbranch || !banksocial.bankbranch.trim()) errors.push('Please enter your Bank Branch.')
  if (!banksocial.accountname || !banksocial.accountname.trim()) errors.push('Please enter your Bank Account Name.')
  if (!banksocial.accountnumber || !banksocial.accountnumber.trim()) errors.push('Please enter your Bank Account Number.')
  if (errors.length > 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Missing Bank Details',
      html: `<div class="text-left text-sm text-slate-700"><ul>${errors.map(e => `<li class="py-1">• ${e}</li>`).join('')}</ul></div>`,
      confirmButtonColor: '#1A237E'
    })
    return false
  }
  return true
}

// Auto sync Full Name to Account Name
const onNameChange = () => {
  emp.firstname = (emp.firstname || '').toUpperCase()
  emp.surname = (emp.surname || '').toUpperCase()
  if (!banksocial.accountname || banksocial.accountname === `${emp.firstname} ${emp.surname}`.trim()) {
    banksocial.accountname = `${emp.firstname} ${emp.surname}`.trim()
  }
}

// Ghana Card formatter: GHA-XXXXXXXXX-X
const formatGhCard = () => {
  let val = (emp.ghcardno || '').toUpperCase().replace(/[^A-Z0-9]/g, '')
  if (val.startsWith('GHA')) {
    val = val.substring(3)
  }
  let formatted = 'GHA-'
  if (val.length > 0) {
    formatted += val.substring(0, 9)
  }
  if (val.length > 9) {
    formatted += '-' + val.substring(9, 10)
  }
  emp.ghcardno = formatted
}

// Name lookup helpers
const getCompanyName = (id) => {
  const c = companyList.value.find(item => item.id === Number(id))
  return c ? c.name : 'Melcom'
}

const getBranchName = (id) => {
  const b = branchList.value.find(item => item.id === Number(id))
  return b ? b.name : 'Melcom Store'
}

// Dynamic Lists
const addChild = () => childrens.value.push({ name: '', gender: 'M', dob: '' })
const removeChild = (i) => childrens.value.splice(i, 1)

const addEducation = () => edus.value.push({ schoolname: '', qualification: 'WASSCE / SSCE', coursetitle: '', fromdate: '', todate: '' })
const removeEducation = (i) => edus.value.splice(i, 1)

const addWorkExp = () => workexps.value.push({ companyname: '', jobtitle: '', reasonforleaving: '', fromdate: '', todate: '', salary: '' })
const removeWorkExp = (i) => workexps.value.splice(i, 1)

const addReference = () => refs.value.push({ name: '', position: '', organization: '', phoneno: '', email: '' })
const removeReference = (i) => refs.value.splice(i, 1)

// File Handlers
const handleProfilePhoto = (e) => {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (event) => {
    emp.profilepicture = event.target.result
    saveDraftToStorage()
  }
  reader.readAsDataURL(file)
}

const handleDocUpload = (e, field) => {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (event) => {
    documents[field] = event.target.result
    saveDraftToStorage()
  }
  reader.readAsDataURL(file)
}

// Canvas Touch Signature
const initCanvas = () => {
  const canvas = sigCanvas.value
  if (!canvas) return
  const ratio = Math.max(window.devicePixelRatio || 1, 1)
  const rect = canvas.getBoundingClientRect()
  canvas.width = rect.width * ratio
  canvas.height = rect.height * ratio
  canvasCtx = canvas.getContext('2d')
  canvasCtx.scale(ratio, ratio)
  canvasCtx.strokeStyle = '#0f172a'
  canvasCtx.lineWidth = 2.5
  canvasCtx.lineCap = 'round'
  canvasCtx.lineJoin = 'round'

  if (emp.signature) {
    const img = new Image()
    img.onload = () => {
      canvasCtx.drawImage(img, 0, 0, rect.width, rect.height)
      hasSignature.value = true
    }
    img.src = emp.signature
  }
}

const getCanvasPos = (evt) => {
  const canvas = sigCanvas.value
  const rect = canvas.getBoundingClientRect()
  return { x: evt.clientX - rect.left, y: evt.clientY - rect.top }
}

const startDrawing = (e) => {
  isDrawing.value = true
  const pos = getCanvasPos(e)
  canvasCtx.beginPath()
  canvasCtx.moveTo(pos.x, pos.y)
}

const draw = (e) => {
  if (!isDrawing.value) return
  const pos = getCanvasPos(e)
  canvasCtx.lineTo(pos.x, pos.y)
  canvasCtx.stroke()
  hasSignature.value = true
}

const stopDrawing = () => {
  if (!isDrawing.value) return
  isDrawing.value = false
  saveSignatureData()
}

const handleTouchStart = (e) => {
  if (e.touches && e.touches[0]) {
    isDrawing.value = true
    const touch = e.touches[0]
    const canvas = sigCanvas.value
    const rect = canvas.getBoundingClientRect()
    canvasCtx.beginPath()
    canvasCtx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top)
  }
}

const handleTouchMove = (e) => {
  if (!isDrawing.value || !e.touches || !e.touches[0]) return
  const touch = e.touches[0]
  const canvas = sigCanvas.value
  const rect = canvas.getBoundingClientRect()
  canvasCtx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top)
  canvasCtx.stroke()
  hasSignature.value = true
}

const handleTouchEnd = () => {
  isDrawing.value = false
  saveSignatureData()
}

const saveSignatureData = () => {
  if (!sigCanvas.value) return
  emp.signature = sigCanvas.value.toDataURL('image/png')
  saveDraftToStorage()
}

const clearSignature = () => {
  if (!sigCanvas.value || !canvasCtx) return
  const canvas = sigCanvas.value
  const rect = canvas.getBoundingClientRect()
  canvasCtx.clearRect(0, 0, rect.width, rect.height)
  hasSignature.value = false
  emp.signature = null
  saveDraftToStorage()
}

// LocalStorage Draft Management
const STORAGE_KEY = 'melcom_online_onboarding_draft'

const saveDraftToStorage = (notify = false) => {
  try {
    const draftPayload = {
      emp,
      spouse,
      childrens: childrens.value,
      nominee,
      econts: econts.value,
      edus: edus.value,
      workexps: workexps.value,
      refs: refs.value,
      banksocial,
      guarantor,
      documents,
      maintab: maintab.value,
      perinfostep: perinfostep.value,
      savedAt: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(draftPayload))
    draftSavedAt.value = draftPayload.savedAt

    if (notify) {
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Draft saved to this device',
        showConfirmButton: false,
        timer: 2000
      })
    }
  } catch (err) {
    console.warn('Draft save error:', err)
  }
}

const loadDraftFromStorage = () => {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return
    const d = JSON.parse(raw)
    if (d.emp) Object.assign(emp, d.emp)
    if (d.spouse) Object.assign(spouse, d.spouse)
    if (d.childrens) childrens.value = d.childrens
    if (d.nominee) Object.assign(nominee, d.nominee)
    if (d.econts) econts.value = d.econts
    if (d.edus) edus.value = d.edus
    if (d.workexps) workexps.value = d.workexps
    if (d.refs) refs.value = d.refs
    if (d.banksocial) Object.assign(banksocial, d.banksocial)
    if (d.guarantor) Object.assign(guarantor, d.guarantor)
    if (d.documents) Object.assign(documents, d.documents)
    if (d.maintab) maintab.value = d.maintab
    if (d.perinfostep) perinfostep.value = d.perinfostep
    if (d.savedAt) draftSavedAt.value = d.savedAt
    if (emp.signature) hasSignature.value = true
  } catch (e) {
    console.warn('Failed to load draft:', e)
  }
}

const promptClearDraft = () => {
  Swal.fire({
    title: 'Reset Onboarding Form?',
    text: 'This will clear all entered details and start a blank form.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#1A237E',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, Reset'
  }).then((res) => {
    if (res.isConfirmed) {
      localStorage.removeItem(STORAGE_KEY)
      window.location.reload()
    }
  })
}

// Submission
const submitApplication = async () => {
  // Validate final checklist requirements
  if (!hasSignature.value && !emp.signature) {
    Swal.fire({
      icon: 'warning',
      title: 'Digital Signature Required',
      text: 'Please sign with your finger or mouse inside the touch signature box.',
      confirmButtonColor: '#1A237E'
    })
    return
  }

  if (!agreedDeclaration.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Declaration Required',
      text: 'Please check the box confirming the Melcom Employee Declaration.',
      confirmButtonColor: '#1A237E'
    })
    return
  }

  submitting.value = true

  const payload = {
    emp: {
      ...emp,
      signature: emp.signature || (sigCanvas.value ? sigCanvas.value.toDataURL('image/png') : null)
    },
    spouse,
    childrens: childrens.value,
    nominee,
    econts: econts.value,
    edus: edus.value,
    workexps: workexps.value,
    refs: refs.value,
    banksocial,
    guarantor,
    irrguar: {
      name: guarantor.name,
      occupation: guarantor.occupation,
      address: guarantor.address,
      phoneno: guarantor.phoneno,
      amount: '5000'
    },
    documents
  }

  try {
    const res = await axios.post('/public/onboard', payload)

    submitting.value = false
    submittedSuccess.value = true
    localStorage.removeItem(STORAGE_KEY)

    Object.assign(successData, {
      reference_number: res.data.reference_number || 'MEL-ONB-PROCESSED',
      employee_code: res.data.employee_code || 'ONB2026',
      candidate_name: res.data.candidate_name || `${emp.firstname} ${emp.surname}`,
      position: res.data.position || emp.joiningposition,
      submission_date: res.data.submission_date || new Date().toLocaleString()
    })

    window.scrollTo({ top: 0, behavior: 'smooth' })

    Swal.fire({
      icon: 'success',
      title: 'Application Submitted!',
      text: 'Your Melcom onboarding application has been successfully submitted.',
      confirmButtonColor: '#1A237E'
    })
  } catch (error) {
    submitting.value = false
    console.error('Submission error:', error)
    const msg = error.response?.data?.message || 'Failed to submit onboarding form. Please check your data and connection.'
    Swal.fire({
      icon: 'error',
      title: 'Submission Error',
      text: msg,
      confirmButtonColor: '#1A237E'
    })
  }
}

const copyReference = async (ref) => {
  if (!ref) return
  try {
    await navigator.clipboard.writeText(ref)
    copyStatusText.value = 'Copied!'
    setTimeout(() => { copyStatusText.value = 'Copy' }, 2500)
  } catch (e) {
    copyStatusText.value = 'Copied'
  }
}

const printSummary = () => window.print()
const startNewApplication = () => {
  localStorage.removeItem(STORAGE_KEY)
  window.location.reload()
}

// Auto-save debounced watcher
let autoSaveTimer = null
watch(
  [emp, nominee, banksocial, guarantor, spouse, childrens, edus, workexps, refs, documents],
  () => {
    if (submittedSuccess.value) return
    clearTimeout(autoSaveTimer)
    autoSaveTimer = setTimeout(() => {
      saveDraftToStorage(false)
    }, 800)
  },
  { deep: true }
)

watch(maintab, (val) => {
  if (val === 4) {
    nextTick(initCanvas)
  }
})

onMounted(() => {
  loadDraftFromStorage()
  window.addEventListener('resize', initCanvas)
  if (maintab.value === 4) {
    nextTick(initCanvas)
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', initCanvas)
})
</script>

<style scoped>
/* ─── Filter Pill Tabs matching Main Onboarding ─── */
.tab-btn {
  border-radius: 12px;
  padding: 9px 15px;
  font-size: 0.825rem;
  font-weight: 700;
  color: #475569;
  background: transparent;
  border: 1px solid transparent;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 7px;
  cursor: pointer;
  white-space: nowrap;
}
.tab-btn:hover {
  background: rgba(255, 255, 255, 0.6);
  color: #1e293b;
}
.activetab {
  background: #1A237E !important;
  color: #ffffff !important;
  box-shadow: 0 4px 12px rgba(26, 35, 126, 0.28) !important;
  border-color: #1A237E !important;
}

/* ─── Section Header styling matching Main Onboarding ─── */
.section-card {
  border-radius: 16px;
  overflow: hidden;
}
.section-header {
  background: #1A237E;
  color: #ffffff;
  padding: 13px 18px;
  font-size: 0.85rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  display: flex;
  align-items: center;
  gap: 9px;
  border-radius: 16px 16px 0 0;
}
.accent-bar {
  display: inline-block;
  width: 4px;
  height: 16px;
  background: #38bdf8; /* Cyan accent line matching main onboarding */
  border-radius: 3px;
  flex-shrink: 0;
}

/* ─── Form Inputs & Dropdowns matching Main Onboarding ─── */
.form-label {
  display: block;
  font-size: 0.775rem;
  font-weight: 800;
  color: #334155;
  margin-bottom: 5px;
  letter-spacing: -0.01em;
}
.form-input,
.form-select {
  width: 100%;
  min-height: 46px;
  padding: 10px 14px;
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
  background-color: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 12px;
  transition: all 0.2s ease;
  outline: none;
}
.form-input:focus,
.form-select:focus {
  border-color: #1A237E;
  box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.12);
}

/* ─── Profile Picture Upload Zone matching Main Onboarding ─── */
.profile-upload-zone {
  border: 2px dashed #c7d2fe;
  border-radius: 14px;
  padding: 20px;
  background: #f8faff;
  transition: all 0.25s ease;
  cursor: pointer;
}
.profile-upload-zone:hover {
  border-color: #1A237E;
  background: #f0f4ff;
}

/* Hide scrollbar for clean horizontal tabs */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fadeIn 0.25s ease-out forwards;
}

@media print {
  header, button, .tabs, .steper {
    display: none !important;
  }
}
</style>
