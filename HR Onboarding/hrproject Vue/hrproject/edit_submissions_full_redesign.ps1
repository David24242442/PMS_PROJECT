$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\hr\SubmissionsView.vue"
$content = Get-Content $path -Raw

# 1. Update filter deck and inject Cards Deck
$pattern = '(?s)<!-- System Governance Filter Deck -->.*?<!-- Loading State -->'
$replacement = @"
        <!-- KPI Cards Deck -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 mt-2 animate-slide-up">
            <div class="bg-white rounded-2xl p-5 shadow-lg shadow-slate-100/80 border border-slate-100/80 flex items-center justify-between">
                <div>
                     <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Submissions</span>
                     <h3 class="text-3xl font-black text-slate-800 tracking-tight mt-1">{{ stats.total }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                     <i class="pi pi-inbox text-lg"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl p-5 shadow-lg shadow-slate-100/80 border border-slate-100/80 flex items-center justify-between">
                <div>
                     <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Line Managers</span>
                     <h3 class="text-3xl font-black text-slate-800 tracking-tight mt-1">{{ groupedSubmissions.length }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                     <i class="pi pi-users text-lg"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-lg shadow-slate-100/80 border border-slate-100/80 flex items-center justify-between">
                <div>
                     <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pending Review</span>
                     <h3 class="text-3xl font-black text-amber-600 tracking-tight mt-1">{{ stats.pending }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                     <i class="pi pi-clock text-lg"></i>
                </div>
            </div>
        </div>

        <!-- Compact Governance Filter Bar -->
        <div class="bg-white rounded-2xl p-4 shadow-lg shadow-slate-100/60 border border-slate-100/80 mb-8 flex flex-col md:flex-row items-center justify-between gap-4 animate-slide-up" style="animation-delay: 200ms">
            <div class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto flex-1">
                <!-- Search -->
                <div class="relative w-full md:max-w-sm group">
                    <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-600 transition-colors"></i>
                    <input v-model="searchQuery" type="text" placeholder="Search by name, code, or manager..." 
                        class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200/60 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-100/40 focus:bg-white transition-all placeholder:text-slate-300" />
                </div>

                <!-- Department -->
                <div class="relative w-full md:max-w-[200px]">
                    <select v-model="filterDepartment" class="w-full appearance-none pl-4 pr-10 py-2.5 bg-slate-50 border border-slate-200/60 rounded-xl text-xs font-semibold text-slate-600 cursor-pointer focus:ring-2 focus:ring-indigo-100/40 focus:bg-white">
                        <option value="">All Departments</option>
                        <option value="HR">HR Operations</option>
                        <option value="IT">Systems / IT</option>
                        <option value="Sales">Revenue / Sales</option>
                        <option value="Operations">Operations Pool</option>
                        <option value="Finance">Capital / Finance</option>
                    </select>
                    <i class="pi pi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                </div>
            </div>

            <!-- Status Filters -->
            <div class="flex items-center p-1 bg-slate-50 border border-slate-200/40 rounded-xl w-full md:w-auto">
                 <button @click="filterStatus = 'all'" :class="filterStatus === 'all' ? 'bg-white shadow-sm text-indigo-700 border-slate-100' : 'text-slate-400 hover:text-slate-600 border-transparent'" class="px-5 py-2 rounded-lg text-[11px] font-black uppercase tracking-wider transition-all border">Total List</button>
                 <button @click="filterStatus = 'pending'" :class="filterStatus === 'pending' ? 'bg-white shadow-sm text-amber-600 border-slate-100' : 'text-slate-400 hover:text-amber-600 border-transparent'" class="px-5 py-2 rounded-lg text-[11px] font-black uppercase tracking-wider transition-all border">Pending</button>
                 <button @click="filterStatus = 'approved'" :class="filterStatus === 'approved' ? 'bg-white shadow-sm text-emerald-600 border-slate-100' : 'text-slate-400 hover:text-emerald-600 border-transparent'" class="px-5 py-2 rounded-lg text-[11px] font-black uppercase tracking-wider transition-all border">Approved</button>
            </div>
        </div>

        <!-- Loading State -->
"@

$content = $content -replace $pattern, $replacement

# 2. ALSO Remove the user injected closing </div> tag right before </template> or template wrapper footer
$content = $content -replace '</div>\s*</div>\s*</template>', '</div>\s*</template>'

Set-Content $path $content -NoNewline
Write-Host "Redesign and fix applied successfully"
